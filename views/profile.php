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
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
                <p class="mt-1 text-sm text-gray-500">View and manage your personal information.</p>
            </div>
            <a href="change_password.php" class="inline-flex items-center px-4 py-2 border border-green-200 shadow-sm text-sm font-medium rounded-lg text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Change Password
            </a>
        </div>
        <?php if (isset($_SESSION['profile_success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['profile_success']); ?></span>
            </div>
            <?php unset($_SESSION['profile_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['profile_error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['profile_error']); ?></span>
            </div>
            <?php unset($_SESSION['profile_error']); ?>
        <?php endif; ?>

        <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
            <div class="px-6 py-8 sm:px-8 flex items-center space-x-6 border-b border-gray-50">
                <div class="flex-shrink-0 relative group">
                    <div class="h-24 w-24 rounded-full bg-green-50 flex items-center justify-center overflow-hidden border-4 border-white shadow-sm">
                        <?php if ($auth->hasRole(ROLE_ADMIN) || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)): ?>
                            <?php if (!empty($profilePicture)): ?>
                                <img src="/uploads/avatars/<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="h-full w-full object-cover">
                            <?php else: ?>
                                <svg class="h-10 w-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            <?php endif; ?>
                        <?php elseif ($auth->hasRole(ROLE_TEACHER)): ?>
                            <i class="ph ph-users text-4xl text-primary"></i>
                        <?php else: ?>
                            <i class="ph ph-user text-4xl text-primary"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">User Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Personal details and application status.</p>
                    
                    <?php if ($auth->hasRole(ROLE_ADMIN) || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)): ?>
                    <div class="mt-4 flex gap-3">
                        <form id="profilePicForm" action="profile.php" method="POST" enctype="multipart/form-data" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <input type="hidden" name="action" value="upload_picture">
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/png" class="hidden" onchange="validateAndSubmitProfilePic(this)">
                            <button type="button" onclick="document.getElementById('profile_picture').click()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <?php echo !empty($profilePicture) ? 'Change Picture' : 'Upload Picture'; ?>
                            </button>
                        </form>
                        
                        <?php if (!empty($profilePicture)): ?>
                        <form action="profile.php" method="POST" class="inline" onsubmit="return handleConfirm(event, 'Are you sure you want to remove your profile picture?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <input type="hidden" name="action" value="remove_picture">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Remove
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Allowed format: PNG. Max size: 1MB.</p>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            function validateAndSubmitProfilePic(input) {
                if (input.files && input.files[0]) {
                    var file = input.files[0];
                    if (file.size > 1048576) {
                        alert("File size must be less than 1MB");
                        input.value = "";
                        return;
                    }
                    if (file.type !== "image/png") {
                        alert("Only PNG images are allowed");
                        input.value = "";
                        return;
                    }
                    document.getElementById('profilePicForm').submit();
                }
            }
            </script>
            <div class="px-6 py-2 sm:px-8">
                <div class="divide-y divide-gray-50">
                    <!-- Full Name -->
                    <div class="py-5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
                        <dt class="text-sm font-semibold text-gray-800 flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-10 w-10 rounded-xl bg-green-50 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </div>
                            Full Name
                        </dt>
                        <dd class="mt-1 text-sm text-gray-600 sm:mt-0 sm:col-span-2"><?php echo e($username ?: 'Not Set'); ?></dd>
                    </div>
                    <!-- Email Address -->
                    <div class="py-5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
                        <dt class="text-sm font-semibold text-gray-800 flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-10 w-10 rounded-xl bg-green-50 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            Email Address
                        </dt>
                        <dd class="mt-1 text-sm text-gray-600 sm:mt-0 sm:col-span-2"><?php echo e($email); ?></dd>
                    </div>
                    <!-- Role -->
                    <div class="py-5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
                        <dt class="text-sm font-semibold text-gray-800 flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-10 w-10 rounded-xl bg-green-50 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                            </div>
                            Role
                        </dt>
                        <dd class="mt-1 text-sm text-gray-600 sm:mt-0 sm:col-span-2"><?php echo e($role); ?></dd>
                    </div>
                    <!-- Status -->
                    <div class="py-5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
                        <dt class="text-sm font-semibold text-gray-800 flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-10 w-10 rounded-xl bg-green-50 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                            Status
                        </dt>
                        <dd class="mt-1 text-sm text-gray-600 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusInt == 0 ? 'bg-[#dcfce7] text-[#166534]' : 'bg-red-100 text-red-800'; ?>">
                                <?php if ($statusInt == 0): ?>
                                <svg class="-ml-1 mr-1.5 h-2 w-2 text-[#166534]" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                <?php endif; ?>
                                <?php echo e($statusText); ?>
                            </span>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/layouts/footer.php'; ?>

