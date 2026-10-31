<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Rahe Nazat Institute</title>
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

        <!-- Back to Login Navigation -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                <i class="ph ph-arrow-left text-[16px]"></i>
                Back to Login
            </a>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 mb-4">
                <i class="ph ph-key text-[28px]"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Forgot Password?</h1>
            <p class="text-sm md:text-base text-gray-500 mt-2">
                No worries! Enter your registered email address and we'll send you a secure link to reset your password.
            </p>
        </div>

        <!-- Success Alert -->
        <?php if (!empty($success)): ?>
            <div class="mb-6 p-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 flex items-start gap-3">
                <i class="ph ph-check-circle text-[22px] text-emerald-600 mt-0.5 flex-shrink-0"></i>
                <div>
                    <h3 class="text-sm font-semibold text-emerald-800">Check your inbox</h3>
                    <p class="text-sm text-emerald-700 mt-0.5"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl border-l-4 border-red-500 bg-red-50 flex items-start gap-3">
                <i class="ph ph-warning-circle text-[22px] text-red-500 mt-0.5 flex-shrink-0"></i>
                <p class="text-sm text-red-700 font-medium"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Forgot Password Form -->
        <form action="forgot_password.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Email Input Group -->
            <div class="mb-6">
                <label for="email" class="auth-label block mb-2 text-gray-700">Registered Email Address</label>
                <div class="input-focus-ring relative flex items-center border border-[#E5E7EB] rounded-xl transition-all duration-200 bg-white overflow-hidden group">
                    <div class="pl-4 pr-3 flex items-center justify-center text-gray-400 group-focus-within:text-[#6B7280] transition-colors">
                        <i class="ph ph-envelope-simple text-[22px]"></i>
                    </div>
                    <input type="email" id="email" name="email"
                        value="<?php echo htmlspecialchars($oldEmail ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="teacher@example.com or student@example.com"
                        class="auth-input w-full py-3.5 pr-4 outline-none placeholder:text-gray-400 bg-transparent"
                        maxlength="254" autocomplete="email" inputmode="email" spellcheck="false" autocorrect="off"
                        autocapitalize="off" required autofocus>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">Applies to both Student and Teacher portal accounts.</p>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="auth-btn w-full py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all duration-200 focus:outline-none shadow-sm cursor-pointer">
                <i class="ph ph-paper-plane-tilt text-[20px]"></i>
                Send Reset Link
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> Rahe Nazat Institute. All rights reserved.
        </div>

    </div>

</body>

</html>

