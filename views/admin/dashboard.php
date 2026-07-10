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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Total Students Card -->
            <a href="/admin/students.php" class="relative bg-white rounded-2xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 group overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-500/5 to-transparent rounded-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                <div class="flex items-center justify-between mb-4 relative">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[13px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        +12%
                    </span>
                </div>
                <div class="relative">
                    <div class="text-[32px] leading-none font-bold text-slate-900 mb-1"><?= htmlspecialchars((string) ($stats['total_students'] ?? 0)) ?></div>
                    <div class="text-[13px] font-medium text-slate-500">Total Students</div>
                </div>
            </a>

            <!-- Total Teachers Card -->
            <a href="/admin/teachers.php" class="relative bg-white rounded-2xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 group overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-emerald-500/5 to-transparent rounded-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                <div class="flex items-center justify-between mb-4 relative">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[13px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        +3%
                    </span>
                </div>
                <div class="relative">
                    <div class="text-[32px] leading-none font-bold text-slate-900 mb-1"><?= htmlspecialchars((string) ($stats['total_teachers'] ?? 0)) ?></div>
                    <div class="text-[13px] font-medium text-slate-500">Total Teachers</div>
                </div>
            </a>

            <!-- Total Notices Card -->
            <a href="/admin/notices/index.php" class="relative bg-white rounded-2xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 group overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-500/5 to-transparent rounded-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                <div class="flex items-center justify-between mb-4 relative">
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                </div>
                <div class="relative">
                    <div class="text-[32px] leading-none font-bold text-slate-900 mb-1"><?= htmlspecialchars((string) ($stats['total_notices'] ?? 0)) ?></div>
                    <div class="text-[13px] font-medium text-slate-500">Total Notices</div>
                </div>
            </a>

            <!-- Total Classrooms Card -->
            <a href="/admin/classrooms/index.php" class="relative bg-white rounded-2xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 group overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-500/5 to-transparent rounded-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                <div class="flex items-center justify-between mb-4 relative">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[13px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        +1
                    </span>
                </div>
                <div class="relative">
                    <div class="text-[32px] leading-none font-bold text-slate-900 mb-1"><?= htmlspecialchars((string) ($stats['total_classrooms'] ?? 0)) ?></div>
                    <div class="text-[13px] font-medium text-slate-500">Total Classrooms</div>
                </div>
            </a>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
            <!-- Recent Activities -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 overflow-hidden flex flex-col h-full min-h-[360px]">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-[16px] font-bold text-slate-900">Recent Activities</h2>
                    <a href="#" class="text-[13px] font-medium text-purple-600 hover:text-purple-700 hover:underline">View All</a>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-center items-center text-center bg-gray-50/50">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm text-slate-300 border border-gray-100">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-[14px] font-semibold text-slate-600">No recent activities found</p>
                    <p class="text-[13px] text-slate-400 mt-1 max-w-sm">When users perform actions, register, or create items, they will appear here as a timeline.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 overflow-hidden flex flex-col h-full">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-[16px] font-bold text-slate-900">Quick Actions</h2>
                </div>
                <div class="p-4 space-y-1.5 flex-1 bg-gray-50/50">
                    <a href="/admin/students.php" class="flex items-center p-3 rounded-xl bg-white hover:shadow-sm border border-transparent hover:border-gray-100 transition-all group">
                        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-[14px] font-bold text-slate-700 group-hover:text-purple-600 transition-colors">Add Student</div>
                            <div class="text-[12px] font-medium text-slate-500 mt-0.5">Register a new student</div>
                        </div>
                    </a>
                    
                    <a href="/admin/notices/create.php" class="flex items-center p-3 rounded-xl bg-white hover:shadow-sm border border-transparent hover:border-gray-100 transition-all group">
                        <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-[14px] font-bold text-slate-700 group-hover:text-orange-600 transition-colors">Publish Notice</div>
                            <div class="text-[12px] font-medium text-slate-500 mt-0.5">Create a new announcement</div>
                        </div>
                    </a>
                    
                    <a href="/admin/classrooms/create.php" class="flex items-center p-3 rounded-xl bg-white hover:shadow-sm border border-transparent hover:border-gray-100 transition-all group">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-[14px] font-bold text-slate-700 group-hover:text-blue-600 transition-colors">New Classroom</div>
                            <div class="text-[12px] font-medium text-slate-500 mt-0.5">Set up a new virtual class</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

