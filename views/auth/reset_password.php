<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Rahe Nazat Institute</title>
    <!-- Tailwind CLI generated CSS -->
    <link href="css/app.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Universal CSS: Colors, Fonts, Sizes -->
    <link href="css/universal.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons -->
    <script src="phosphor-icons/index.js"></script>
    <style>
        .auth-body {
            font-family: var(--font-family-primary);
            background-color: #F5F6F8;
            color: var(--color-black);
        }

        .auth-card {
            background-color: var(--color-white);
        }

        .auth-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
        }

        .auth-input {
            font-size: var(--font-size-sm);
            color: var(--color-black);
        }

        .auth-btn {
            background-color: var(--color-primary-green);
            color: var(--color-white);
            font-weight: var(--font-weight-medium);
            font-size: var(--font-size-base);
        }

        .auth-btn:hover {
            filter: brightness(0.9);
        }

        .input-focus-ring:focus-within {
            border-color: var(--color-primary-green);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
    </style>
</head>

<body class="auth-body min-h-screen flex items-center justify-center p-4">

    <!-- Card Container -->
    <div class="auth-card w-full max-w-[480px] p-8 md:p-10 rounded-2xl shadow-sm border border-[#E5E7EB]">

        <?php if (!$isTokenValid): ?>
            <!-- ── INVALID OR EXPIRED TOKEN STATE ────────────────────────── -->
            <div class="text-center py-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 text-amber-600 mb-5">
                    <i class="ph ph-warning-circle text-[32px]"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Link Expired or Invalid</h1>
                <p class="text-sm text-gray-600 mt-3 leading-relaxed">
                    <?php echo htmlspecialchars($tokenError ?? 'This password reset link is invalid or has expired.', ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div class="mt-8 flex flex-col gap-3">
                    <a href="forgot_password.php"
                        class="auth-btn w-full py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all duration-200 shadow-sm">
                        <i class="ph ph-arrow-counter-clockwise text-[20px]"></i>
                        Request New Reset Link
                    </a>
                    <a href="index.php"
                        class="w-full py-3 px-4 rounded-xl flex items-center justify-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                        Return to Login
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- ── VALID TOKEN: NEW PASSWORD FORM ────────────────────────── -->

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 mb-4">
                    <i class="ph ph-shield-check text-[28px]"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Set New Password</h1>
                <p class="text-sm md:text-base text-gray-500 mt-2">
                    Please create a strong new password to protect your account.
                </p>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl border-l-4 border-red-500 bg-red-50 flex items-start gap-3">
                    <i class="ph ph-warning-circle text-[22px] text-red-500 mt-0.5 flex-shrink-0"></i>
                    <p class="text-sm text-red-700 font-medium"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            <?php endif; ?>

            <form action="reset_password.php" method="POST" id="resetForm">
                <!-- Hidden Security & Token Fields -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <!-- New Password Field -->
                <div class="mb-5">
                    <label for="password" class="auth-label block mb-2 text-gray-700">New Password</label>
                    <div class="input-focus-ring relative flex items-center border border-[#E5E7EB] rounded-xl transition-all duration-200 bg-white overflow-hidden group">
                        <div class="pl-4 pr-3 flex items-center justify-center text-gray-400 group-focus-within:text-[#6B7280] transition-colors">
                            <i class="ph ph-lock-key text-[22px]"></i>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Enter new password"
                            class="auth-input w-full py-3.5 outline-none placeholder:text-gray-400 bg-transparent"
                            minlength="8" maxlength="128" autocomplete="new-password" required autofocus>
                        <button type="button" id="togglePassword"
                            class="pr-4 pl-3 flex items-center justify-center text-gray-400 hover:text-[#6B7280] transition-colors focus:outline-none">
                            <i id="eyeIcon1" class="ph ph-eye-slash text-[22px]"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-5">
                    <label for="password_confirmation" class="auth-label block mb-2 text-gray-700">Confirm New Password</label>
                    <div class="input-focus-ring relative flex items-center border border-[#E5E7EB] rounded-xl transition-all duration-200 bg-white overflow-hidden group">
                        <div class="pl-4 pr-3 flex items-center justify-center text-gray-400 group-focus-within:text-[#6B7280] transition-colors">
                            <i class="ph ph-lock-key-open text-[22px]"></i>
                        </div>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password"
                            class="auth-input w-full py-3.5 outline-none placeholder:text-gray-400 bg-transparent"
                            minlength="8" maxlength="128" autocomplete="new-password" required>
                        <button type="button" id="toggleConfirmPassword"
                            class="pr-4 pl-3 flex items-center justify-center text-gray-400 hover:text-[#6B7280] transition-colors focus:outline-none">
                            <i id="eyeIcon2" class="ph ph-eye-slash text-[22px]"></i>
                        </button>
                    </div>
                    <div id="matchMessage" class="text-xs mt-1.5 hidden"></div>
                </div>

                <!-- Password Requirements Checklist -->
                <div class="mb-6 p-4 rounded-xl bg-gray-50 border border-gray-100 text-xs space-y-1.5">
                    <p class="font-semibold text-gray-700 mb-2">Password Requirements:</p>
                    <div id="req-length" class="flex items-center gap-2 text-gray-500">
                        <i class="ph ph-circle text-[14px]"></i>
                        <span>At least 8 characters long</span>
                    </div>
                    <div id="req-upper" class="flex items-center gap-2 text-gray-500">
                        <i class="ph ph-circle text-[14px]"></i>
                        <span>Contains uppercase letter (A-Z)</span>
                    </div>
                    <div id="req-lower" class="flex items-center gap-2 text-gray-500">
                        <i class="ph ph-circle text-[14px]"></i>
                        <span>Contains lowercase letter (a-z)</span>
                    </div>
                    <div id="req-number" class="flex items-center gap-2 text-gray-500">
                        <i class="ph ph-circle text-[14px]"></i>
                        <span>Contains at least one number (0-9)</span>
                    </div>
                    <div id="req-special" class="flex items-center gap-2 text-gray-500">
                        <i class="ph ph-circle text-[14px]"></i>
                        <span>Contains special character (!@#$%^&*)</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="auth-btn w-full py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all duration-200 focus:outline-none shadow-sm cursor-pointer">
                    <i class="ph ph-check-circle text-[20px]"></i>
                    Reset & Update Password
                </button>
            </form>

            <script>
                // Password Toggles
                function setupToggle(toggleId, inputId, iconId) {
                    const toggleBtn = document.getElementById(toggleId);
                    const input = document.getElementById(inputId);
                    const icon = document.getElementById(iconId);
                    if (!toggleBtn || !input || !icon) return;

                    toggleBtn.addEventListener('click', function () {
                        const isPassword = input.getAttribute('type') === 'password';
                        input.setAttribute('type', isPassword ? 'text' : 'password');
                        if (isPassword) {
                            icon.classList.remove('ph-eye-slash');
                            icon.classList.add('ph-eye');
                        } else {
                            icon.classList.remove('ph-eye');
                            icon.classList.add('ph-eye-slash');
                        }
                    });
                }

                setupToggle('togglePassword', 'password', 'eyeIcon1');
                setupToggle('toggleConfirmPassword', 'password_confirmation', 'eyeIcon2');

                // Real-time Strength & Match Checker
                const passInput = document.getElementById('password');
                const confirmInput = document.getElementById('password_confirmation');
                const matchMsg = document.getElementById('matchMessage');

                const reqLength = document.getElementById('req-length');
                const reqUpper = document.getElementById('req-upper');
                const reqLower = document.getElementById('req-lower');
                const reqNumber = document.getElementById('req-number');
                const reqSpecial = document.getElementById('req-special');

                function updateReq(el, isValid) {
                    const icon = el.querySelector('i');
                    if (isValid) {
                        el.classList.remove('text-gray-500');
                        el.classList.add('text-emerald-600', 'font-medium');
                        icon.className = 'ph ph-check-circle text-[14px] text-emerald-600';
                    } else {
                        el.classList.remove('text-emerald-600', 'font-medium');
                        el.classList.add('text-gray-500');
                        icon.className = 'ph ph-circle text-[14px] text-gray-400';
                    }
                }

                function validateForm() {
                    const val = passInput.value;
                    const confirmVal = confirmInput.value;

                    updateReq(reqLength, val.length >= 8);
                    updateReq(reqUpper, /[A-Z]/.test(val));
                    updateReq(reqLower, /[a-z]/.test(val));
                    updateReq(reqNumber, /[0-9]/.test(val));
                    updateReq(reqSpecial, /[^A-Za-z0-9]/.test(val));

                    if (confirmVal.length > 0) {
                        matchMsg.classList.remove('hidden');
                        if (val === confirmVal) {
                            matchMsg.textContent = 'Passwords match!';
                            matchMsg.className = 'text-xs mt-1.5 text-emerald-600 font-medium flex items-center gap-1';
                        } else {
                            matchMsg.textContent = 'Passwords do not match.';
                            matchMsg.className = 'text-xs mt-1.5 text-red-500 font-medium flex items-center gap-1';
                        }
                    } else {
                        matchMsg.classList.add('hidden');
                    }
                }

                passInput.addEventListener('input', validateForm);
                confirmInput.addEventListener('input', validateForm);
            </script>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> Rahe Nazat Institute. All rights reserved.
        </div>

    </div>

</body>

</html>

