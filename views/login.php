<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rahen Azat Institute</title>
    <!-- We will use Tailwind CLI generated CSS -->
    <link href="css/app.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Universal CSS: Colors, Fonts, Sizes -->
    <link href="css/universal.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Google Fonts: Inter for the clean look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons for the input icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* All design tokens come from universal.css */
        .login-body {
            font-family: var(--font-family-primary);
            background-color: #F5F6F8;
            color: var(--color-black);
        }

        .login-card {
            background-color: var(--color-white);
        }

        .login-title {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
        }

        .login-subtitle {
            font-size: var(--font-size-lg);
            color: #6B7280;
        }

        .login-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
        }

        .login-input {
            font-size: var(--font-size-sm);
            color: var(--color-black);
        }

        .login-forgot {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            color: var(--color-primary-green);
        }

        .login-forgot:hover {
            text-decoration: underline;
        }

        .login-remember-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            color: var(--color-black);
        }

        .login-btn {
            background-color: var(--color-primary-green);
            color: var(--color-white);
            font-weight: var(--font-weight-medium);
            font-size: var(--font-size-base);
        }

        .login-btn:hover {
            filter: brightness(0.9);
        }

        .login-error {
            border-left-color: var(--color-red);
        }

        .login-error-text {
            font-size: var(--font-size-sm);
            color: var(--color-red);
        }

        .login-footer {
            font-size: var(--font-size-xs);
            color: #6B7280;
            font-weight: var(--font-weight-medium);
        }

        .login-logo {
            background-color: var(--color-primary-green);
        }

        .login-checkbox:checked {
            background-color: var(--color-primary-green);
            border-color: var(--color-primary-green);
        }

        .input-focus-ring:focus-within {
            border-color: var(--color-primary-green);
            box-shadow: 0 0 0 1px var(--color-primary-green);
        }
    </style>
</head>

<body class="login-body min-h-screen flex items-center justify-center p-4 antialiased">

    <!-- Main Card Container -->
    <div
        class="login-card rounded-[24px] shadow-sm w-full max-w-2xl px-6 py-12 sm:px-16 sm:py-16 flex flex-col items-center">

        <!-- Header with Logo -->
        <div class="mb-6 flex justify-center">
            <div
                class="login-logo w-20 h-20 rounded-full flex items-center justify-center shadow-inner border-[6px] border-green-50 ring-1 ring-gray-100">
                <i class="ph ph-book-open text-white text-[32px]"></i>
            </div>
        </div>

        <h1 class="login-title mb-2 text-center tracking-tight">Login</h1>
        <p class="login-subtitle mb-10 text-center">RahenazatInstitute</p>

        <?php if (!empty($error)): ?>
            <div class="login-error bg-red-50 border-l-4 p-4 rounded-md mb-8 w-full max-w-[480px]">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="ph ph-warning-circle text-xl" style="color: var(--color-red);"></i>
                    </div>
                    <div class="ml-3">
                        <p class="login-error-text"><?php echo e($error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Area -->
        <form id="loginForm" action="index.php" method="POST" class="w-full max-w-[480px]" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">

            <!-- Email Input Group -->
            <div class="mb-6">
                <label for="email" class="login-label block mb-2">Email Address</label>
                <div
                    class="input-focus-ring relative flex items-center border border-[#E5E7EB] rounded-xl transition-all duration-200 bg-white overflow-hidden group">
                    <div
                        class="pl-4 pr-3 flex items-center justify-center text-gray-400 group-focus-within:text-[#6B7280] transition-colors">
                        <i class="ph ph-envelope-simple text-[22px]"></i>
                    </div>
                    <input type="email" id="email" name="email" value="<?php echo e($oldEmail ?? ''); ?>"
                        placeholder="Enter your email address"
                        class="login-input w-full py-3.5 pr-4 outline-none placeholder:text-gray-400 bg-transparent"
                        maxlength="254"
                        autocomplete="email"
                        inputmode="email"
                        spellcheck="false"
                        autocorrect="off"
                        autocapitalize="off"
                        required>
                </div>
            </div>

            <!-- Password Input Group -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="login-label block">Password</label>
                    <a href="#" class="login-forgot">Forgot Password?</a>
                </div>
                <div
                    class="input-focus-ring relative flex items-center border border-[#E5E7EB] rounded-xl transition-all duration-200 bg-white overflow-hidden group">
                    <div
                        class="pl-4 pr-3 flex items-center justify-center text-gray-400 group-focus-within:text-[#6B7280] transition-colors">
                        <i class="ph ph-lock-key text-[22px]"></i>
                    </div>
                    <input type="password" id="password" name="password" placeholder="Enter your password"
                        class="login-input w-full py-3.5 outline-none placeholder:text-gray-400 bg-transparent"
                        minlength="8"
                        maxlength="128"
                        autocomplete="current-password"
                        pattern="[\x20-\x7E]+"
                        title="Password must be 8–128 characters using standard keyboard characters only"
                        required>
                    <button type="button" id="togglePassword"
                        class="pr-4 pl-3 flex items-center justify-center text-gray-400 hover:text-[#6B7280] transition-colors focus:outline-none">
                        <i id="eyeIcon" class="ph ph-eye-slash text-[22px]"></i>
                    </button>
                </div>
            </div>

            <!-- Keep me logged in Checkbox -->
            <div class="mb-8 flex items-center">
                <div class="relative flex items-center">
                    <input type="checkbox" id="keepLogged" name="remember_me" value="1"
                        class="login-checkbox peer appearance-none w-[20px] h-[20px] border border-gray-400 rounded bg-white cursor-pointer transition-colors">
                    <i
                        class="ph ph-check absolute text-white text-sm pointer-events-none opacity-0 peer-checked:opacity-100 left-[3px] top-[3px]"></i>
                </div>
                <label for="keepLogged" class="login-remember-label ml-3 cursor-pointer">Keep me logged in</label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="login-btn w-full py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all duration-200 focus:outline-none shadow-sm">
                <i class="ph ph-lock-key text-[20px]"></i>
                Login
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer mt-12 text-center">
            &copy; <?php echo date('Y'); ?> Rahenazat Institute. All rights reserved.
        </div>

    </div>

    <!-- JavaScript for Password Toggle -->
    <script>
        // ── Password Toggle ───────────────────────────────────────────────
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput     = document.getElementById('password');
        const eyeIcon           = document.getElementById('eyeIcon');
        const emailInput        = document.getElementById('email');
        const loginForm         = document.getElementById('loginForm');

        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            if (type === 'password') {
                eyeIcon.classList.remove('ph-eye');
                eyeIcon.classList.add('ph-eye-slash');
            } else {
                eyeIcon.classList.remove('ph-eye-slash');
                eyeIcon.classList.add('ph-eye');
            }
        });

        // ── Client-Side Sanitization on Submit ───────────────────────────
        loginForm.addEventListener('submit', function (e) {
            // 1. Trim & normalise email
            const rawEmail = emailInput.value.trim().toLowerCase();
            emailInput.value = rawEmail;

            // 2. Basic email format check (defence-in-depth; browser already validates type=email)
            const emailRegex = /^[^\s@]{1,64}@[^\s@]+\.[^\s@]{2,}$/;
            if (!emailRegex.test(rawEmail) || rawEmail.length > 254) {
                e.preventDefault();
                alert('Please enter a valid email address (max 254 characters).');
                emailInput.focus();
                return;
            }

            // 3. Password: strip null-bytes & control characters (ASCII < 32 except none allowed)
            const rawPass   = passwordInput.value;
            // Allow only printable ASCII (0x20–0x7E)
            const cleanPass = rawPass.replace(/[^\x20-\x7E]/g, '');

            if (cleanPass !== rawPass) {
                e.preventDefault();
                alert('Password contains invalid characters. Only standard keyboard characters are allowed.');
                passwordInput.value = '';
                passwordInput.focus();
                return;
            }

            // 4. Length guards (server also enforces, this is early feedback)
            if (cleanPass.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                passwordInput.focus();
                return;
            }
            if (cleanPass.length > 128) {
                e.preventDefault();
                alert('Password must not exceed 128 characters.');
                passwordInput.focus();
                return;
            }

            // 5. Prevent double-submission
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ph ph-circle-notch text-[20px] animate-spin"></i> Logging in…';
        });
    </script>
</body>

</html>