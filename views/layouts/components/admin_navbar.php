<!-- Top Navbar -->
<header
    class="h-20 bg-white shadow-sm border-b border-gray-200 flex items-center justify-between px-4 lg:px-8 shrink-0 z-20 sticky top-0">

    <!-- Left side (Menu Toggle) -->
    <div class="flex items-center gap-4">
        <!-- Hamburger Menu (Mobile/Tablet) -->
        <button id="openSidebar"
            class="p-2 -ml-2 text-gray-500 hover:text-primary rounded-lg lg:hidden focus:outline-none">
            <i class="ph ph-list text-2xl"></i>
        </button>
    </div>

    <!-- Right side (Search, Notification, Profile) -->
    <div class="flex items-center justify-end w-full lg:w-auto gap-4 sm:gap-6">

        <!-- Profile Dropdown -->
        <div class="relative">
            <button id="profileDropdownBtn"
                class="flex items-center gap-3 cursor-pointer focus:outline-none p-1 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-teal-600 to-primary flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-white overflow-hidden">
                    <?php if (!empty($adminProfilePic)): ?>
                        <img src="/uploads/avatars/<?php echo htmlspecialchars($adminProfilePic); ?>" alt="Profile" class="h-full w-full object-cover">
                    <?php else: ?>
                        <?php echo strtoupper(substr($auth->getUsername() ?: $auth->getEmail(), 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-sm font-bold text-brandText leading-tight"><?php echo e($auth->getUsername() ?: $auth->getEmail()); ?></p>
                    <p class="text-xs text-gray-500">Super Admin</p>
                </div>
                <i class="ph ph-caret-down text-sm text-gray-400 hidden sm:block"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="profileDropdownMenu"
                class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50 transform origin-top-right transition-all">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                    <p class="text-sm font-bold text-brandText"><?php echo e($auth->getUsername() ?: $auth->getEmail()); ?></p>
                    <p class="text-xs text-gray-500 mt-0.5">Administrator</p>
                </div>
                <div class="py-1">
                    <a href="/profile.php"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-primary/5 hover:text-primary transition-colors">
                        <i class="ph ph-user-circle text-lg"></i> My Profile
                    </a>
                    <a href="/admin/settings/meetings.php"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-primary/5 hover:text-primary transition-colors">
                        <i class="ph ph-video-camera text-lg"></i> Meetings
                    </a>
                </div>
                <div class="border-t border-gray-100 my-1"></div>
                <form action="/logout.php" method="POST" class="w-full m-0">
                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i class="ph ph-sign-out text-lg"></i> Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
