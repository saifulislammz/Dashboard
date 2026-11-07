<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Rahe Nazat Institute</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css?v=<?php echo file_exists(__DIR__ . '/../public/css/login.css') ? filemtime(__DIR__ . '/../public/css/login.css') : time(); ?>">
</head>
<body>
  <main class="login-page">
    <section class="brand-panel" aria-label="Rahe Nazat Institute">
      <div class="brand">
        <picture>
          <source srcset="images/Rahenazat-Logo-White.webp" type="image/webp">
          <img src="images/Rahenazat-Logo-White.png" alt="Rahe Nazat Institute" class="brand-logo">
        </picture>
      </div>
      <div class="hero-copy">
        <h1>Learn Quran,<br>Arabic &amp; Islamic<br>Knowledge Online</h1>
        <div class="gold-rule"></div>
        <p>A structured, accessible, and authentic<br class="desktop-break"> Islamic learning platform for everyone,<br class="desktop-break"> anywhere in the world.</p>
      </div>
    </section>

    <section class="form-panel" aria-label="Login form">
      <header class="form-header">
        <h2>Welcome Back</h2>
        <p>Log in to your Rahe Nazat Institute account</p>
      </header>

      <?php if (!empty($error)): ?>
        <div class="auth-alert auth-alert-error" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span><?php echo e($error); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="auth-alert auth-alert-success" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          <span><?php echo e($success); ?></span>
        </div>
      <?php endif; ?>

      <form id="loginForm" method="POST" action="index.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
        
        <div class="field">
          <div class="field-heading">
            <label class="field-label" for="email">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
              Email Address
            </label>
          </div>
          <div class="input-wrap">
            <input id="email" name="email" type="email" autocomplete="username" placeholder="Enter your email address" value="<?php echo e($oldEmail ?? ''); ?>" maxlength="254" required spellcheck="false" autocorrect="off" autocapitalize="off">
          </div>
        </div>

        <div class="field">
          <div class="field-heading">
            <label class="field-label" for="password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg>
              Password
            </label>
            <a class="forgot" href="forgot_password.php" id="forgotPassword">Forgot Password?</a>
          </div>
          <div class="input-wrap">
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" minlength="8" maxlength="128" pattern="[\x20-\x7E]+" title="Password must be 8–128 characters using standard keyboard characters only" required>
            <button class="toggle-password" type="button" id="togglePassword" aria-label="Show password">
              <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c5 0 9 7 9 7a16 16 0 0 1-3.1 3.7M6.2 6.2C3.9 7.8 3 12 3 12s4 7 9 7a10 10 0 0 0 3.1-.5"/></svg>
            </button>
          </div>
        </div>

        <label class="remember-row" for="remember">
          <input type="checkbox" name="remember_me" id="remember" value="1">
          <span>Keep me logged in</span>
        </label>

        <button class="login-button" type="submit" id="submitBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
          Log In
        </button>
        <p class="notice" id="notice" role="status"></p>
      </form>

      <footer class="copyright">© <?php echo date('Y'); ?> Rahe Nazat Institute. All rights reserved.</footer>
    </section>
  </main>

  <script nonce="<?php echo csp_nonce(); ?>">
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');

    togglePassword.addEventListener('click', () => {
      const show = passwordInput.type === 'password';
      passwordInput.type = show ? 'text' : 'password';
      togglePassword.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      eyeIcon.innerHTML = show
        ? '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>'
        : '<path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c5 0 9 7 9 7a16 16 0 0 1-3.1 3.7M6.2 6.2C3.9 7.8 3 12 3 12s4 7 9 7a10 10 0 0 0 3.1-.5"/>';
    });

    loginForm.addEventListener('submit', (e) => {
      const rawEmail = emailInput.value.trim().toLowerCase();
      emailInput.value = rawEmail;

      const emailRegex = /^[^\s@]{1,64}@[^\s@]+\.[^\s@]{2,}$/;
      if (!emailRegex.test(rawEmail) || rawEmail.length > 254) {
        e.preventDefault();
        alert('Please enter a valid email address (max 254 characters).');
        emailInput.focus();
        return;
      }

      const rawPass = passwordInput.value;
      const cleanPass = rawPass.replace(/[^\x20-\x7E]/g, '');
      if (cleanPass !== rawPass) {
        e.preventDefault();
        alert('Password contains invalid characters. Standard keyboard characters only.');
        passwordInput.focus();
        return;
      }

      if (cleanPass.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        passwordInput.focus();
        return;
      }

      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.8';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;animation:spin 1s linear infinite;" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Logging In…';
    });
  </script>
</body>
</html>
