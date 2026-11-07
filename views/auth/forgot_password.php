<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Rahe Nazat Institute</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/forgot_password.css?v=<?php echo file_exists(__DIR__ . '/../../public/css/forgot_password.css') ? filemtime(__DIR__ . '/../../public/css/forgot_password.css') : time(); ?>">
</head>
<body>
  <main class="page">
    <section class="brand-panel" aria-label="Rahe Nazat Institute">
      <div class="brand">
        <picture>
          <source srcset="images/Rahenazat-Logo-White.webp" type="image/webp">
          <img src="images/Rahenazat-Logo-White.png" alt="Rahe Nazat Institute" class="brand-logo">
        </picture>
      </div>
      <div class="hero-copy">
        <h1>Reset Your<br>Password</h1>
        <div class="gold-rule"></div>
        <p>Enter your registered email address<br>and we’ll send you secure link to<br>reset your password.</p>
      </div>
    </section>

    <section class="card" aria-label="Forgot password form">
      <a class="back-link" href="index.php" aria-label="Back to Login">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to Login
      </a>

      <div class="card-content">
        <div class="mail-badge" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="2" y="4" width="20" height="16" rx="1.5"/>
            <path d="m3 5 9 8 9-8M3 19l6-7M21 19l-6-7"/>
          </svg>
        </div>
        <h2>Forgot Password?</h2>

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

        <form id="resetForm" method="POST" action="forgot_password.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken ?? ''); ?>">
          
          <label class="field-label" for="email">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
            Registered Email Address
          </label>
          <input class="email-input" id="email" name="email" type="email" autocomplete="email" placeholder="Enter your email address" value="<?php echo e($oldEmail ?? ''); ?>" maxlength="254" required spellcheck="false" autocorrect="off" autocapitalize="off">
          <p class="helper">Applies to both <strong>Student</strong> and <strong>Teacher</strong> portal accounts.</p>

          <button class="submit" type="submit" id="submitBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            Send Reset Link
          </button>
        </form>
      </div>

      <footer class="copyright">© <?php echo date('Y'); ?> Rahe Nazat Institute. All rights reserved.</footer>
    </section>
  </main>

  <script nonce="<?php echo csp_nonce(); ?>">
    const resetForm = document.getElementById('resetForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');

    resetForm.addEventListener('submit', (e) => {
      const email = emailInput.value.trim().toLowerCase();
      emailInput.value = email;

      const emailRegex = /^[^\s@]{1,64}@[^\s@]+\.[^\s@]{2,}$/;
      if (!email || !emailRegex.test(email) || email.length > 254) {
        e.preventDefault();
        alert('Please enter a valid email address (max 254 characters).');
        emailInput.focus();
        return;
      }

      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.8';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;animation:spin 1s linear infinite;" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Sending…';
    });
  </script>
</body>
</html>
