<?php global $auth; ?>
<!-- Mobile Backdrop Overlay -->
<div id="sidebar-backdrop"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 hidden md:hidden transition-opacity duration-300 opacity-0"
    onclick="closeSidebar()"></div>

<style>
    @media (min-width: 768px) {
        #admin-sidebar {
            position: static !important;
        }
    }
</style>

<!-- Sidebar -->
<aside id="admin-sidebar" class="
    fixed inset-y-0 left-0 z-40
    w-[280px] shrink-0 flex flex-col bg-white border-r border-gray-100 shadow-[2px_0_10px_rgba(0,0,0,0.05)]
    transform -translate-x-full md:translate-x-0
    transition-transform duration-300 ease-in-out
    md:flex
">
    <!-- Logo area -->
    <div class="h-20 flex items-center justify-between px-6 shrink-0 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <div class="text-[#00d084]">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z"></path>
                    <path d="M2 17L12 22L22 17"></path>
                    <path d="M2 12L12 17L22 12"></path>
                </svg>
            </div>
            <span class="text-xl font-bold text-gray-900 tracking-tight mt-1">Rahen Azat Institute</span>
        </div>
        <!-- Close button (mobile only) -->
        <button onclick="closeSidebar()"
            class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
            aria-label="Close sidebar">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <!-- Dashboard -->
        <a href="/admin/dashboard.php"
            class="group flex items-center px-4 py-3 text-sm font-semibold rounded-xl <?php echo ($activeMenu ?? '') === 'dashboard' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#334155] hover:bg-gray-50'; ?>"
            onclick="closeSidebar()">
            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Dashboard
        </a>

        <?php
        $isUserMgmtActive   = in_array($activeMenu ?? '', ['teachers', 'students']);
        $isNoticeActive      = in_array($activeMenu ?? '', ['notices_create', 'notices_manage']);
        $isClassroomsActive  = in_array($activeMenu ?? '', ['classrooms_create', 'classrooms_manage']);
        $isAttendanceActive  = in_array($activeMenu ?? '', ['admin_attendance']);
        $isAccountActive     = in_array($activeMenu ?? '', ['profile']);
        $isSettingsActive    = in_array($activeMenu ?? '', ['settings_meetings']);
        ?>

        <!-- User Management -->
        <div class="mt-6 pt-2 nav-group is-open">
            <div class="flex items-center justify-between px-2 py-2 mb-1 cursor-pointer nav-group-toggle group/toggle">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#7c3aed]"></div>
                    <span
                        class="text-[11px] font-bold text-[#64748b] uppercase tracking-wider group-hover/toggle:text-[#475569] transition-colors">USER
                        MANAGEMENT</span>
                </div>
                <div
                    class="w-6 h-6 rounded border border-[#ddd6fe] flex items-center justify-center text-[#7c3aed] bg-white group-hover/toggle:bg-purple-50 transition-all duration-200 nav-group-icon <?php echo !$isUserMgmtActive && $activeMenu ? 'rotate-180' : ''; ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </div>
            </div>
            <div
                class="grid <?php echo !$isUserMgmtActive && $activeMenu ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]'; ?> transition-[grid-template-rows] duration-300 ease-in-out nav-group-content">
                <div class="overflow-hidden">
                    <div class="space-y-0.5 pb-1">
                        <a href="/admin/teachers.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'teachers' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            Teachers
                        </a>
                        <a href="/admin/students.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'students' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z">
                                </path>
                            </svg>
                            Students
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOTICE BOARD -->
        <div class="mt-4 pt-2 nav-group <?php echo $isNoticeActive || !$activeMenu ? 'is-open' : ''; ?>">
            <div class="flex items-center justify-between px-2 py-2 mb-1 cursor-pointer nav-group-toggle group/toggle">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#7c3aed]"></div>
                    <span
                        class="text-[11px] font-bold text-[#64748b] uppercase tracking-wider group-hover/toggle:text-[#475569] transition-colors">NOTICE
                        BOARD</span>
                </div>
                <div
                    class="w-6 h-6 rounded border border-[#ddd6fe] flex items-center justify-center text-[#7c3aed] bg-white group-hover/toggle:bg-purple-50 transition-all duration-200 nav-group-icon <?php echo !$isNoticeActive && $activeMenu ? 'rotate-180' : ''; ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </div>
            </div>
            <div
                class="grid <?php echo !$isNoticeActive && $activeMenu ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]'; ?> transition-[grid-template-rows] duration-300 ease-in-out nav-group-content">
                <div class="overflow-hidden">
                    <div class="space-y-0.5 pb-1">
                        <a href="/admin/notices/create.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'notices_create' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Create Notice
                        </a>
                        <a href="/admin/notices/index.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'notices_manage' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Manage Notices
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLASSROOMS -->
        <div class="mt-4 pt-2 nav-group <?php echo $isClassroomsActive || !$activeMenu ? 'is-open' : ''; ?>">
            <div class="flex items-center justify-between px-2 py-2 mb-1 cursor-pointer nav-group-toggle group/toggle">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#7c3aed]"></div>
                    <span
                        class="text-[11px] font-bold text-[#64748b] uppercase tracking-wider group-hover/toggle:text-[#475569] transition-colors">CLASSROOMS</span>
                </div>
                <div
                    class="w-6 h-6 rounded border border-[#ddd6fe] flex items-center justify-center text-[#7c3aed] bg-white group-hover/toggle:bg-purple-50 transition-all duration-200 nav-group-icon <?php echo !$isClassroomsActive && $activeMenu ? 'rotate-180' : ''; ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </div>
            </div>
            <div
                class="grid <?php echo !$isClassroomsActive && $activeMenu ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]'; ?> transition-[grid-template-rows] duration-300 ease-in-out nav-group-content">
                <div class="overflow-hidden">
                    <div class="space-y-0.5 pb-1">
                        <a href="/admin/classrooms/create.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'classrooms_create' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Create Classroom
                        </a>
                        <a href="/admin/classrooms/index.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'classrooms_manage' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            Manage Classrooms
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ATTENDANCE -->
        <div class="mt-4 pt-2 nav-group <?php echo $isAttendanceActive || !$activeMenu ? 'is-open' : ''; ?>">
            <div class="flex items-center justify-between px-2 py-2 mb-1 cursor-pointer nav-group-toggle group/toggle">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#0ea5e9]"></div>
                    <span class="text-[11px] font-bold text-[#64748b] uppercase tracking-wider group-hover/toggle:text-[#475569] transition-colors">ATTENDANCE</span>
                </div>
                <div class="w-6 h-6 rounded border border-[#bae6fd] flex items-center justify-center text-[#0ea5e9] bg-white group-hover/toggle:bg-sky-50 transition-all duration-200 nav-group-icon <?php echo !$isAttendanceActive && $activeMenu ? 'rotate-180' : ''; ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </div>
            </div>
            <div class="grid <?php echo !$isAttendanceActive && $activeMenu ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]'; ?> transition-[grid-template-rows] duration-300 ease-in-out nav-group-content">
                <div class="overflow-hidden">
                    <div class="space-y-0.5 pb-1">
                        <a href="/admin/attendance/overview.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'admin_attendance' ? 'bg-[#f0f9ff] text-[#0ea5e9]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                </path>
                            </svg>
                            Attendance Overview
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- SETTINGS -->
        <div class="mt-4 pt-2 nav-group <?php echo $isSettingsActive || !$activeMenu ? 'is-open' : ''; ?>">
            <div class="flex items-center justify-between px-2 py-2 mb-1 cursor-pointer nav-group-toggle group/toggle">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></div>
                    <span
                        class="text-[11px] font-bold text-[#64748b] uppercase tracking-wider group-hover/toggle:text-[#475569] transition-colors">SETTINGS</span>
                </div>
                <div
                    class="w-6 h-6 rounded border border-[#a7f3d0] flex items-center justify-center text-[#10b981] bg-white group-hover/toggle:bg-emerald-50 transition-all duration-200 nav-group-icon <?php echo !$isSettingsActive && $activeMenu ? 'rotate-180' : ''; ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </div>
            </div>
            <div
                class="grid <?php echo !$isSettingsActive && $activeMenu ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]'; ?> transition-[grid-template-rows] duration-300 ease-in-out nav-group-content">
                <div class="overflow-hidden">
                    <div class="space-y-0.5 pb-1">
                        <a href="/admin/settings/meetings.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'settings_meetings' ? 'bg-[#ecfdf5] text-[#10b981]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Meetings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCOUNT -->
        <div class="mt-4 pt-2 nav-group <?php echo $isAccountActive || !$activeMenu ? 'is-open' : ''; ?>">
            <div class="flex items-center justify-between px-2 py-2 mb-1 cursor-pointer nav-group-toggle group/toggle">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#7c3aed]"></div>
                    <span
                        class="text-[11px] font-bold text-[#64748b] uppercase tracking-wider group-hover/toggle:text-[#475569] transition-colors">ACCOUNT</span>
                </div>
                <div
                    class="w-6 h-6 rounded border border-[#ddd6fe] flex items-center justify-center text-[#7c3aed] bg-white group-hover/toggle:bg-purple-50 transition-all duration-200 nav-group-icon <?php echo !$isAccountActive && $activeMenu ? 'rotate-180' : ''; ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </div>
            </div>
            <div
                class="grid <?php echo !$isAccountActive && $activeMenu ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]'; ?> transition-[grid-template-rows] duration-300 ease-in-out nav-group-content">
                <div class="overflow-hidden">
                    <div class="space-y-0.5 pb-1">
                        <a href="/profile.php"
                            class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl <?php echo ($activeMenu ?? '') === 'profile' ? 'bg-[#f5f3ff] text-[#7c3aed]' : 'text-[#475569] hover:bg-gray-50'; ?>"
                            onclick="closeSidebar()">
                            <svg class="mr-4 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        if (window.innerWidth >= 768) {
            // Desktop: toggle visibility by toggling a hidden class specifically for desktop
            // We use a custom hidden behavior to ensure flex doesn't override it.
            if (sidebar.style.display === 'none') {
                sidebar.style.display = 'flex';
            } else {
                sidebar.style.display = 'none';
            }
        } else {
            // Mobile: toggle slide in/out
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                requestAnimationFrame(() => {
                    backdrop.classList.remove('opacity-0');
                    backdrop.classList.add('opacity-100');
                });
            } else {
                closeSidebar();
            }
        }
    }

    function closeSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        // Only close on mobile (or if someone clicks a link on mobile)
        if (window.innerWidth < 768) {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Nav group accordion toggle
        const navGroups = document.querySelectorAll('.nav-group');
        navGroups.forEach(group => {
            const toggle = group.querySelector('.nav-group-toggle');
            const content = group.querySelector('.nav-group-content');
            const icon = group.querySelector('.nav-group-icon');

            toggle.addEventListener('click', () => {
                const isOpen = group.classList.contains('is-open');
                if (isOpen) {
                    group.classList.remove('is-open');
                    content.classList.remove('grid-rows-[1fr]');
                    content.classList.add('grid-rows-[0fr]');
                    icon.classList.add('rotate-180');
                } else {
                    group.classList.add('is-open');
                    content.classList.remove('grid-rows-[0fr]');
                    content.classList.add('grid-rows-[1fr]');
                    icon.classList.remove('rotate-180');
                }
            });
        });

        // Close sidebar on window resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                const backdrop = document.getElementById('sidebar-backdrop');
                backdrop.classList.add('hidden', 'opacity-0');
                backdrop.classList.remove('opacity-100');
            }
        });
    });
</script>

<!-- Main content wrapper -->
<div class="flex-1 flex flex-col h-screen overflow-hidden bg-[#fafafa]">
    <!-- Top navbar -->
    <header
        class="h-20 flex items-center justify-between px-4 md:px-8 bg-white border-b border-gray-100 shadow-sm z-10 relative shrink-0">
        <!-- Mobile & Desktop Toggle + Mobile Logo -->
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" id="hamburger-btn"
                class="w-10 h-10 md:hidden flex items-center justify-center rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors"
                aria-label="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="text-xl font-bold text-gray-900 tracking-tight md:hidden">Rahen Azat Institute</span>
        </div>

        <!-- Desktop: Left empty space -->
        <div class="hidden md:block flex-1"></div>

        <!-- Right actions -->
        <div class="flex items-center gap-4 md:gap-6">
            <span class="text-[15px] font-semibold text-[#1e293b] hidden sm:block">Welcome,
                <?php echo e($auth->getUsername() ?: $auth->getEmail()); ?></span>

            <div class="flex items-center gap-3 md:gap-4">
                <form action="/logout.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                    <button type="submit"
                        class="w-10 h-10 bg-[#fef2f2] rounded-xl flex items-center justify-center text-[#ef4444] hover:bg-red-100 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>