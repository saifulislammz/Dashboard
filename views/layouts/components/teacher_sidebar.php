<?php
global $auth, $db;
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

        <div class="mb-2">
            <a href="/teacher/dashboard.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($activeMenu ?? '') === 'dashboard' ? 'shadow-sm bg-primary text-white shadow-green-500/30' : 'text-gray-600 hover:bg-gray-50 hover:text-primary transition-colors'; ?>">
                <i class="ph ph-squares-four text-xl"></i>
                <span class="font-medium">Dashboard</span>
            </a>
        </div>

        <div class="text-[11px] font-bold text-gray-400 mt-5 mb-2 px-4 uppercase tracking-widest">Academic
        </div>

        <a href="/teacher/classrooms.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-colors <?php echo ($activeMenu ?? '') === 'classes' ? 'text-primary font-bold bg-primary/5' : 'text-gray-600 hover:bg-gray-50 hover:text-primary font-medium'; ?>">
            <i class="ph ph-chalkboard text-xl"></i>
            <span class="text-sm">Classes</span>
        </a>

        <a href="/teacher/sessions.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-colors <?php echo ($activeMenu ?? '') === 'teacher_sessions' ? 'text-primary font-bold bg-primary/5' : 'text-gray-600 hover:bg-gray-50 hover:text-primary font-medium'; ?>">
            <i class="ph ph-video-camera text-xl"></i>
            <span class="text-sm">Live Sessions</span>
        </a>

        <a href="/teacher/attendance.php<?php echo isset($classroom) ? '?classroom_id=' . (int) $classroom['id'] : ''; ?>"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-colors <?php echo ($activeMenu ?? '') === 'teacher_attendance' ? 'text-primary font-bold bg-primary/5' : 'text-gray-600 hover:bg-gray-50 hover:text-primary font-medium'; ?>">
            <i class="ph ph-calendar-check text-xl"></i>
            <span class="text-sm">Attendance</span>
        </a>

        <div class="text-[11px] font-bold text-gray-400 mt-5 mb-2 px-4 uppercase tracking-widest">Personal
        </div>

        <a href="/profile.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-colors <?php echo ($activeMenu ?? '') === 'profile' ? 'text-primary font-bold bg-primary/5' : 'text-gray-600 hover:bg-gray-50 hover:text-primary font-medium'; ?>">
            <i class="ph ph-user-circle text-xl"></i>
            <span class="text-sm">Profile</span>
        </a>

    </div>
</aside>