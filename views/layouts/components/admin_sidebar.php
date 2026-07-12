<?php
global $auth, $db;
$adminProfilePic = null;
if (isset($auth) && $auth->isLoggedIn() && isset($db)) {
    $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $auth->getUserId()]);
    $adminProfilePic = $stmt->fetchColumn();
}
?>
<!-- Overlay for mobile sidebar -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<!-- Sidebar / Navigation -->
<aside id="sidebar"
    class="bg-white w-72 h-full flex-shrink-0 border-r border-gray-100 flex flex-col fixed lg:relative z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">

    <!-- Sidebar Header / Logo (Desktop) -->
    <div class="h-20 flex items-center px-6 border-b border-transparent lg:border-gray-100 hidden lg:flex">
        <div class="flex items-center gap-2">
            <img src="/images/rahe_nazat.png" alt="Rahe Nazat Logo" class="h-10 w-auto object-contain">
            <span class="font-bold text-lg tracking-tight">Rahe Nazat Institute</span>
        </div>
    </div>

    <!-- Sidebar Header / Logo (Mobile) -->
    <div class="lg:hidden flex items-center justify-between h-20 px-6 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <img src="/images/rahe_nazat.png" alt="Rahe Nazat Logo" class="h-8 w-auto object-contain">
            <span class="font-bold text-lg tracking-tight">Rahe Nazat Institute</span>
        </div>
        <button id="closeSidebar" class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
            <i class="ph ph-x text-xl"></i>
        </button>
    </div>

    <!-- Navigation Links Wrapper -->
    <div
        class="flex-1 overflow-y-auto py-6 px-4 flex flex-col gap-1 [scrollbar-width:auto] [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-gray-300">

        <?php
        $isUserMgmtActive = in_array($activeMenu ?? '', ['teachers', 'students']);
        $isNoticeActive = in_array($activeMenu ?? '', ['notices_create', 'notices_manage']);
        $isClassroomsActive = in_array($activeMenu ?? '', ['classrooms_create', 'classrooms_manage', 'classrooms_meetings']);
        $isAttendanceActive = in_array($activeMenu ?? '', ['admin_attendance']);
        $isInvoiceActive = in_array($activeMenu ?? '', ['invoice_create', 'invoice_dashboard', 'invoice_settings']);
        $isQuizActive = in_array($activeMenu ?? '', ['quiz_list', 'quiz_create', 'quiz_view'], true);
        $isAccountActive = in_array($activeMenu ?? '', ['profile']);
        ?>

        <div class="mb-2">
            <a href="/admin/dashboard.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-sm bg-primary text-white shadow-green-500/30">
                <i class="ph ph-squares-four text-xl"></i>
                <span class="font-medium">Dashboard</span>
            </a>
        </div>

        <div class="text-[11px] font-bold text-gray-400 mt-5 mb-2 px-4 uppercase tracking-widest">Management
        </div>

        <!-- USER MANAGEMENT -->
        <div>
            <button onclick="toggleMenu('menu-user', 'icon-user')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isUserMgmtActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-users text-xl <?php echo $isUserMgmtActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">USER MANAGEMENT</span>
                </div>
                <i id="icon-user"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isUserMgmtActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-user"
                class="<?php echo $isUserMgmtActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/admin/teachers.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'teachers' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-chalkboard-teacher text-lg"></i> Teachers
                </a>
                <a href="/admin/students.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'students' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-student text-lg"></i> Students
                </a>
            </div>
        </div>

        <!-- NOTICE BOARD -->
        <div>
            <button onclick="toggleMenu('menu-notice', 'icon-notice')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isNoticeActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-megaphone text-xl <?php echo $isNoticeActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">NOTICE BOARD</span>
                </div>
                <i id="icon-notice"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isNoticeActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-notice"
                class="<?php echo $isNoticeActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/admin/notices/create.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'notices_create' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-plus-circle text-lg"></i> Create Notice
                </a>
                <a href="/admin/notices/index.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'notices_manage' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-list-bullets text-lg"></i> Manage Notices
                </a>
            </div>
        </div>

        <!-- CLASSROOMS -->
        <div>
            <button onclick="toggleMenu('menu-class', 'icon-class')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isClassroomsActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-chalkboard text-xl <?php echo $isClassroomsActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">CLASSROOMS</span>
                </div>
                <i id="icon-class"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isClassroomsActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-class"
                class="<?php echo $isClassroomsActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/admin/classrooms/create.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'classrooms_create' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-plus-square text-lg"></i> Create Classroom
                </a>
                <a href="/admin/classrooms/index.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'classrooms_manage' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-kanban text-lg"></i> Manage Classrooms
                </a>
                <a href="/admin/settings/meetings.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'classrooms_meetings' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-video-camera text-lg"></i> Meetings
                </a>
            </div>
        </div>

        <!-- ATTENDANCE -->
        <div>
            <button onclick="toggleMenu('menu-attendance', 'icon-attendance')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isAttendanceActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-calendar-check text-xl <?php echo $isAttendanceActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">ATTENDANCE</span>
                </div>
                <i id="icon-attendance"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isAttendanceActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-attendance"
                class="<?php echo $isAttendanceActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/admin/attendance/overview.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'admin_attendance' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-chart-bar text-lg"></i> Attendance Overview
                </a>
            </div>
        </div>

        <div class="text-[11px] font-bold text-gray-400 mt-5 mb-2 px-4 uppercase tracking-widest">Configuration
        </div>

        <!-- SETTINGS removed -->

        <!-- INVOICES -->
        <div>
            <button onclick="toggleMenu('menu-invoices', 'icon-invoices')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isInvoiceActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-receipt text-xl <?php echo $isInvoiceActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">INVOICES</span>
                </div>
                <i id="icon-invoices"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isInvoiceActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-invoices"
                class="<?php echo $isInvoiceActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/admin/invoices/create.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'invoice_create' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-file-plus text-lg"></i> Generate Invoice
                </a>
                <a href="/admin/invoices/index.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'invoice_dashboard' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-presentation-chart text-lg"></i> Dashboard
                </a>
                <a href="/admin/invoices/settings.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'invoice_settings' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-gear-six text-lg"></i> Settings
                </a>
            </div>
        </div>

        <!-- QUIZ -->
        <div>
            <button onclick="toggleMenu('menu-quiz', 'icon-quiz')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isQuizActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-question text-xl <?php echo $isQuizActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">QUIZ</span>
                    <?php
                    // For Admin, show all pending quizzes
                    $quizRepo = new \App\Repositories\QuizRepository($db);
                    $pendingQuizzesCount = $quizRepo->getGlobalUnreviewedVoiceCount();
                    if ($pendingQuizzesCount > 0):
                        $badge = $pendingQuizzesCount > 99 ? '99+' : $pendingQuizzesCount;
                        ?>
                        <span
                            class="ml-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold bg-[#059669] text-white rounded-full"><?php echo (int) $badge; ?></span>
                    <?php endif; ?>
                </div>
                <i id="icon-quiz"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isQuizActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-quiz"
                class="<?php echo $isQuizActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/admin/quiz/index.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'quiz_list' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-list-numbers text-lg"></i> Quiz List
                </a>
            </div>
        </div>

        <div class="text-[11px] font-bold text-gray-400 mt-5 mb-2 px-4 uppercase tracking-widest">Personal
        </div>

        <!-- ACCOUNT -->
        <div>
            <button onclick="toggleMenu('menu-account', 'icon-account')"
                class="w-full flex items-center justify-between px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-primary rounded-xl transition-colors group focus:outline-none <?php echo $isAccountActive ? 'text-primary' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="ph ph-user-circle text-xl <?php echo $isAccountActive ? 'text-primary' : 'group-hover:text-primary'; ?>"></i>
                    <span class="font-medium text-sm">ACCOUNT</span>
                </div>
                <i id="icon-account"
                    class="ph ph-caret-down text-sm transition-transform duration-300 <?php echo $isAccountActive ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="menu-account"
                class="<?php echo $isAccountActive ? 'flex' : 'hidden'; ?> flex-col mt-1 pl-12 pr-4 space-y-1">
                <a href="/profile.php"
                    class="flex items-center gap-2 py-2 text-sm <?php echo ($activeMenu ?? '') === 'profile' ? 'text-primary font-bold' : 'text-gray-500 hover:text-primary font-medium'; ?> transition-colors">
                    <i class="ph ph-user-focus text-lg"></i> Profile
                </a>
            </div>
        </div>

    </div>
</aside>