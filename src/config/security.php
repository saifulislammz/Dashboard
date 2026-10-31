<?php

// Strictly enforce secure sessions
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');  // Lax allows cross-page redirects on HTTPS
        ini_set('session.cookie_secure', '1');        // Required for HTTPS production
    }
    @session_start();
}

/**
 * Generate or retrieve request-scoped cryptographically secure CSP Nonce
 */
function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

// Security Headers
if (!headers_sent()) {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(self), camera=()");
    
    $nonce = csp_nonce();
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com 'unsafe-eval'; style-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; img-src 'self' data: https://images.unsplash.com; font-src 'self' data: https://fonts.gstatic.com https://unpkg.com; media-src 'self' blob:;");
    
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    
    // XSS Protection for older browsers (though CSP covers this)
    header("X-XSS-Protection: 1; mode=block");
}

// Output escaping function
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
