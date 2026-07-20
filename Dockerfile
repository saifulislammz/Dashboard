# =================================================================
# Stage 1 – Node 22: Build Tailwind CSS v4
# =================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# Copy everything Tailwind needs to scan for classes
COPY tailwind.config.js postcss.config.js ./
COPY public/css/input.css ./public/css/input.css
COPY public/css/universal.css ./public/css/universal.css
COPY views/ ./views/
COPY public/ ./public/
COPY src/ ./src/

# Build & minify Tailwind output (use binary directly — npx may fail in Alpine)
RUN ./node_modules/.bin/tailwindcss \
      -i ./public/css/input.css \
      -o ./public/css/app.css \
      --minify

# =================================================================
# Stage 2 – Composer 2: Install PHP production dependencies
# =================================================================
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --optimize-autoloader \
      --no-interaction \
      --no-scripts \
      --ignore-platform-reqs

# =================================================================
# Stage 3 – PHP 8.3-FPM + Nginx (Production)
# =================================================================
FROM php:8.3-fpm-alpine AS production

# ── System packages ───────────────────────────────────────────────
RUN apk add --no-cache \
      nginx \
      supervisor \
      curl \
      libpng-dev \
      libjpeg-turbo-dev \
      freetype-dev \
      libzip-dev \
      icu-dev \
      oniguruma-dev \
      openssl-dev \
      && docker-php-ext-configure gd \
           --with-freetype \
           --with-jpeg \
      && docker-php-ext-install -j"$(nproc)" \
           pdo \
           pdo_mysql \
           mysqli \
           mbstring \
           gd \
           zip \
           intl \
           opcache \
           pcntl \
           bcmath

# ── PHP config ────────────────────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/php.ini  "$PHP_INI_DIR/conf.d/99-custom.ini"
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# ── Nginx config ──────────────────────────────────────────────────
COPY docker/nginx/nginx.conf   /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# ── Supervisor config ─────────────────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application ───────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

# Inject compiled CSS and vendor from earlier stages
COPY --from=node-builder     --chown=www-data:www-data /app/public/css/app.css ./public/css/app.css
COPY --from=composer-builder --chown=www-data:www-data /app/vendor             ./vendor

# Ensure writable runtime directories exist
RUN mkdir -p storage logs public/uploads \
    && chown -R www-data:www-data storage logs public/uploads \
    && chmod -R 775 storage logs public/uploads

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
