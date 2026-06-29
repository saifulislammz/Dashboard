<?php global $auth; ?>
<!-- Mobile Backdrop Overlay -->
<div id="student-sidebar-backdrop"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 hidden md:hidden transition-opacity duration-300 opacity-0"
    onclick="closeStudentSidebar()"></div>

<style>
    @media (min-width: 768px) {
        #student-sidebar {
            position: static !important;
        }
    }
</style>

<!-- Sidebar -->
<aside id="student-sidebar" class="
    fixed inset-y-0 left-0 z-40
    w-64 shrink-0 flex flex-col bg-white border-r border-gray-200
    transform -translate-x-full md:translate-x-0
    transition-transform duration-300 ease-in-out
    md:flex
">
    <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200 shrink-0">
        <div class="flex items-center gap-2">
            <div class="text-primary mr-2">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z"></path>
                    <path d="M2 17L12 22L22 17"></path>
                    <path d="M2 12L12 17L22 12"></path>
                </svg>
            </div>
            <span class="text-xl font-bold text-gray-900 tracking-tight">Rahen Azat Institute</span>
        </div>
        <!-- Close button (mobile only) -->
        <button onclick="closeStudentSidebar()"
            class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
            aria-label="Close sidebar">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <a href="/student/dashboard.php"
            class="<?php echo ($activeMenu ?? '') === 'dashboard' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
            onclick="closeStudentSidebar()">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 transition-colors" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Dashboard
        </a>
        <a href="/student/classrooms.php"
            class="<?php echo ($activeMenu ?? '') === 'classes' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
            onclick="closeStudentSidebar()">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 transition-colors" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                </path>
            </svg>
            Classes
        </a>
        <a href="/student/sessions.php"
            class="<?php echo ($activeMenu ?? '') === 'student_sessions' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
            onclick="closeStudentSidebar()">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 transition-colors" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                </path>
            </svg>
            Live Classes
        </a>
        <a href="/student/attendance.php?classroom_id=<?php echo isset($classroom) ? (int)$classroom['id'] : ''; ?>"
            class="<?php echo ($activeMenu ?? '') === 'student_attendance' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
            onclick="closeStudentSidebar()">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 transition-colors" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                </path>
            </svg>
            My Attendance
        </a>
        <a href="/profile.php"
            class="<?php echo ($activeMenu ?? '') === 'profile' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
            onclick="closeStudentSidebar()">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 transition-colors" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Profile
        </a>
    </nav>
</aside>

<script>
    function toggleStudentSidebar() {
        const sidebar = document.getElementById('student-sidebar');
        const backdrop = document.getElementById('student-sidebar-backdrop');
        
        if (window.innerWidth >= 768) {
            if (sidebar.style.display === 'none') {
                sidebar.style.display = 'flex';
            } else {
                sidebar.style.display = 'none';
            }
        } else {
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                requestAnimationFrame(() => {
                    backdrop.classList.remove('opacity-0');
                    backdrop.classList.add('opacity-100');
                });
            } else {
                closeStudentSidebar();
            }
        }
    }
    function closeStudentSidebar() {
        if (window.innerWidth < 768) {
            const sidebar = document.getElementById('student-sidebar');
            const backdrop = document.getElementById('student-sidebar-backdrop');
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
        }
    }
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            const backdrop = document.getElementById('student-sidebar-backdrop');
            if (backdrop) {
                backdrop.classList.add('hidden', 'opacity-0');
                backdrop.classList.remove('opacity-100');
            }
        }
    });
</script>

<!-- Main content -->
<div class="flex-1 flex flex-col h-screen overflow-hidden">
    <!-- Top navbar -->
    <header
        class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0">
        <!-- Mobile & Desktop Toggle + Mobile Logo -->
        <div class="flex items-center gap-3">
            <button onclick="toggleStudentSidebar()"
                class="w-10 h-10 md:hidden flex items-center justify-center rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors"
                aria-label="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="text-xl font-bold text-gray-900 tracking-tight md:hidden">Rahen Azat Institute</span>
        </div>

        <div class="flex-1 flex justify-end">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-700 hidden sm:block">Welcome,
                    <?php echo e($auth->getUsername() ?: $auth->getEmail()); ?></span>
                <form action="/logout.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                    <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded text-red-600 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </header>