<?php
$pageTitle = 'Admin Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-8 lg:p-10 bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto space-y-8">
        <div class="flex flex-col gap-1">
            <h1 class="text-[28px] font-bold text-slate-900 tracking-tight">Admin Dashboard</h1>
            <p class="text-[15px] text-slate-600 font-medium">Here's an overview of your system.</p>
        </div>
        
        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    
            <!-- Stat Card 1: Students -->
            <a href="/admin/students.php" class="bg-cardBg rounded-2xl p-6 shadow-sm shadow-gray-200/50 border border-gray-100/50 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-iconBgGreen flex items-center justify-center text-green-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">group</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Students</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_students'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <span class="text-green-500 font-bold flex items-center"><span class="material-symbols-outlined text-[14px] mr-0.5">arrow_upward</span>12%</span> from last month
                    </p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,40 Q20,40 30,20 T60,30 T100,5" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 2: Teachers -->
            <a href="/admin/teachers.php" class="bg-cardBg rounded-2xl p-6 shadow-sm shadow-gray-200/50 border border-gray-100/50 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-iconBgTeal flex items-center justify-center text-teal-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">school</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Teachers</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_teachers'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <span class="text-green-500 font-bold flex items-center"><span class="material-symbols-outlined text-[14px] mr-0.5">arrow_upward</span>8%</span> from last month
                    </p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,45 Q20,30 40,40 T70,20 T100,10" fill="none" stroke="#0EA5E9" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 3: Notices -->
            <a href="/admin/notices/index.php" class="bg-cardBg rounded-2xl p-6 shadow-sm shadow-gray-200/50 border border-gray-100/50 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-iconBgOrange flex items-center justify-center text-orange-500">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">notifications</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Notices</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_notices'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <span class="text-orange-500 font-bold flex items-center"><span class="material-symbols-outlined text-[14px] mr-0.5">arrow_upward</span>15%</span> from last month
                    </p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,40 Q30,40 40,25 T70,30 T100,15" fill="none" stroke="#F59E0B" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 4: Classrooms -->
            <a href="/admin/classrooms/index.php" class="bg-cardBg rounded-2xl p-6 shadow-sm shadow-gray-200/50 border border-gray-100/50 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-iconBgBlue flex items-center justify-center text-blue-500">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">business</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Classrooms</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_classrooms'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <span class="text-blue-500 font-bold flex items-center"><span class="material-symbols-outlined text-[14px] mr-0.5">arrow_upward</span>20%</span> from last month
                    </p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,45 Q20,40 30,25 T60,35 T100,5" fill="none" stroke="#3B82F6" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 5: Quizzes -->
            <a href="/admin/quiz/index.php" class="bg-cardBg rounded-2xl p-6 shadow-sm shadow-gray-200/50 border border-gray-100/50 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-iconBgPurple flex items-center justify-center text-purple-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">quiz</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Quizzes</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_quizzes'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <span class="text-purple-500 font-bold flex items-center"><span class="material-symbols-outlined text-[14px] mr-0.5">arrow_upward</span>10%</span> from last month
                    </p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,45 Q20,40 30,25 T60,35 T100,5" fill="none" stroke="#9333EA" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>
        </div>


    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

