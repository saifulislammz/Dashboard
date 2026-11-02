<?php
$pageTitle = 'Profile';
$activeMenu = 'profile';

// Determine correct sidebar based on role
require __DIR__ . '/layouts/header.php';
if ($auth->hasRole(ROLE_ADMIN) || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)) {
    require __DIR__ . '/layouts/sidebar_admin.php';
} elseif ($auth->hasRole(ROLE_TEACHER)) {
    require __DIR__ . '/layouts/sidebar_teacher.php';
} else {
    require __DIR__ . '/layouts/sidebar_student.php';
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-5xl mx-auto space-y-8">
        <!-- Top Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
                <p class="mt-1 text-sm text-gray-500">View and update your personal details and profile credentials.</p>
            </div>
            <a href="change_password.php" class="inline-flex items-center px-4 py-2 border border-green-200 shadow-xs text-sm font-medium rounded-xl text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Change Password
            </a>
        </div>

        <!-- Feedback Alerts -->
        <?php if (isset($_SESSION['profile_success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl shadow-xs flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <i class="ph ph-check-circle text-xl text-green-600"></i>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['profile_success'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800 focus:outline-none">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            <?php unset($_SESSION['profile_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['profile_error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl shadow-xs flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <i class="ph ph-warning-circle text-xl text-red-600"></i>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['profile_error'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800 focus:outline-none">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            <?php unset($_SESSION['profile_error']); ?>
        <?php endif; ?>

        <!-- Main Profile Card -->
        <div class="bg-white shadow-xs border border-gray-100 rounded-2xl overflow-hidden divide-y divide-gray-100">
            <!-- Avatar & Header Summary Section -->
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start gap-6 bg-gradient-to-b from-gray-50/50 to-white">
                <div class="relative group shrink-0">
                    <div class="h-28 w-28 rounded-full bg-emerald-50 flex items-center justify-center overflow-hidden border-4 border-white shadow-md ring-1 ring-gray-100">
                        <?php if (!empty($profilePic)): ?>
                            <img src="/uploads/avatars/<?php echo htmlspecialchars($profilePic, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="h-full w-full object-cover">
                        <?php else: ?>
                            <span class="text-3xl font-extrabold text-primary uppercase">
                                <?php echo htmlspecialchars(mb_substr($username ?: $email, 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-1 text-center sm:text-left space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo e($username ?: 'Not Set'); ?></h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 self-center sm:self-auto">
                            <?php echo e($role); ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $statusInt === 0 ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?> self-center sm:self-auto">
                            <?php echo e($statusText); ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500"><?php echo e($email); ?></p>

                    <!-- Avatar Upload / Remove Actions -->
                    <div class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <form id="profilePicForm" action="profile.php" method="POST" enctype="multipart/form-data" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="upload_picture">
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" class="hidden">
                            <button type="button" id="uploadPicBtn" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 border border-gray-200 shadow-xs text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                <i class="ph ph-camera text-sm text-primary"></i>
                                <?php echo !empty($profilePic) ? 'Change Picture' : 'Upload Picture'; ?>
                            </button>
                        </form>

                        <?php if (!empty($profilePic)): ?>
                        <form action="profile.php" method="POST" class="inline" data-confirm="Are you sure you want to remove your profile picture?">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="remove_picture">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-xs font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <i class="ph ph-trash text-sm"></i>
                                Remove
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400">Supported formats: JPG, PNG, WEBP. Maximum file size: 2MB.</p>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <form action="profile.php" method="POST" class="p-6 sm:p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Personal Information</h3>
                    <p class="text-sm text-gray-500">Update your personal contact details, birthday, and location.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-user text-lg"></i>
                            </div>
                            <input type="text" id="name" name="name" required maxlength="100"
                                   value="<?php echo htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <!-- Email Address (Read-only for security) -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-envelope-simple text-lg"></i>
                            </div>
                            <input type="email" id="email" disabled
                                   value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-sm text-gray-500 cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Primary login email address is protected. Contact an administrator to change.</p>
                    </div>

                    <!-- Birthday / Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-semibold text-gray-700 mb-1">
                            Birthday (Date of Birth)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-calendar text-lg"></i>
                            </div>
                            <input type="date" id="date_of_birth" name="date_of_birth"
                                   max="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                        <?php if (!empty($profile['age'])): ?>
                            <p class="mt-1 text-xs text-gray-500">Calculated age: <strong class="text-gray-700"><?php echo (int) $profile['age']; ?> years</strong></p>
                        <?php endif; ?>
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-semibold text-gray-700 mb-1">
                            Gender
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-gender-intersex text-lg"></i>
                            </div>
                            <select id="gender" name="gender" class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors bg-white">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">
                            Phone Number
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-phone text-lg"></i>
                            </div>
                            <input type="tel" id="phone" name="phone" maxlength="30" placeholder="+880 1XXX-XXXXXX"
                                   value="<?php echo htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <!-- Country -->
                    <div>
                        <label for="country" class="block text-sm font-semibold text-gray-700 mb-1">
                            Country
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-globe-hemisphere-west text-lg"></i>
                            </div>
                            <input type="text" id="country" name="country" maxlength="100" placeholder="e.g. Bangladesh"
                                   value="<?php echo htmlspecialchars($profile['country'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <!-- City -->
                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-1">
                            City / District
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-buildings text-lg"></i>
                            </div>
                            <input type="text" id="city" name="city" maxlength="100" placeholder="e.g. Dhaka"
                                   value="<?php echo htmlspecialchars($profile['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <!-- Address (Full width) -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-700 mb-1">
                            Full Address / Street Location
                        </label>
                        <div class="relative">
                            <textarea id="address" name="address" rows="3" maxlength="1000" placeholder="Enter house no, street, postal code..."
                                      class="block w-full rounded-xl border border-gray-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"><?php echo htmlspecialchars($profile['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl shadow-xs text-sm font-semibold text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                        <i class="ph ph-floppy-disk text-lg"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script nonce="<?php echo csp_nonce(); ?>">
document.addEventListener('DOMContentLoaded', function() {
    var uploadBtn = document.getElementById('uploadPicBtn');
    var fileInput = document.getElementById('profile_picture');

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files[0]) {
                var file = fileInput.files[0];

                // Size limit: 2MB (2,097,152 bytes)
                if (file.size > 2097152) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Profile picture must be less than 2MB in size.',
                        icon: 'error',
                        confirmButtonColor: '#0d9488'
                    });
                    fileInput.value = '';
                    return;
                }

                // Format validation
                var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        title: 'Invalid Format',
                        text: 'Only JPG, PNG, and WEBP image formats are supported.',
                        icon: 'error',
                        confirmButtonColor: '#0d9488'
                    });
                    fileInput.value = '';
                    return;
                }

                document.getElementById('profilePicForm').submit();
            }
        });
    }
});
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
