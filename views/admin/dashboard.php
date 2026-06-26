<?php
$pageTitle = 'Admin Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-8 lg:p-10 bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto space-y-8">
        <div class="flex flex-col gap-1">
            <h1 class="text-[28px] font-bold text-[#1e293b] tracking-tight">Admin Dashboard</h1>
            <p class="text-[15px] text-[#64748b]">Here's an overview of your system.</p>
        </div>
        
        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Total Students Card -->
            <a href="/admin/students.php" class="bg-white rounded-[20px] p-6 shadow-[0_4px_24px_rgba(0,0,0,0.02)] flex items-center hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] transition-all cursor-pointer">
                <div class="w-[60px] h-[60px] rounded-full bg-[#f5f3ff] text-[#8b5cf6] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="flex-1 ml-5">
                    <div class="text-[11px] font-bold text-[#94a3b8] uppercase tracking-wider mb-1.5">TOTAL STUDENTS</div>
                    <div class="text-[32px] leading-none font-bold text-[#0f172a]"><?= htmlspecialchars((string) ($stats['total_students'] ?? 0)) ?></div>
                </div>
            </a>

            <!-- Total Teachers Card -->
            <a href="/admin/teachers.php" class="bg-white rounded-[20px] p-6 shadow-[0_4px_24px_rgba(0,0,0,0.02)] flex items-center hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] transition-all cursor-pointer">
                <div class="w-[60px] h-[60px] rounded-full bg-[#ecfdf5] text-[#10b981] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex-1 ml-5">
                    <div class="text-[11px] font-bold text-[#94a3b8] uppercase tracking-wider mb-1.5">TOTAL TEACHERS</div>
                    <div class="text-[32px] leading-none font-bold text-[#0f172a]"><?= htmlspecialchars((string) ($stats['total_teachers'] ?? 0)) ?></div>
                </div>
            </a>

            <!-- Total Notices Card -->
            <a href="/admin/notices/index.php" class="bg-white rounded-[20px] p-6 shadow-[0_4px_24px_rgba(0,0,0,0.02)] flex items-center hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] transition-all cursor-pointer">
                <div class="w-[60px] h-[60px] rounded-full bg-[#fff7ed] text-[#f97316] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1 ml-5">
                    <div class="text-[11px] font-bold text-[#94a3b8] uppercase tracking-wider mb-1.5">TOTAL NOTICES</div>
                    <div class="text-[32px] leading-none font-bold text-[#0f172a]"><?= htmlspecialchars((string) ($stats['total_notices'] ?? 0)) ?></div>
                </div>
            </a>

            <!-- Total Classrooms Card -->
            <a href="/admin/classrooms/index.php" class="bg-white rounded-[20px] p-6 shadow-[0_4px_24px_rgba(0,0,0,0.02)] flex items-center hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] transition-all cursor-pointer">
                <div class="w-[60px] h-[60px] rounded-full bg-[#eff6ff] text-[#3b82f6] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="flex-1 ml-5">
                    <div class="text-[11px] font-bold text-[#94a3b8] uppercase tracking-wider mb-1.5">TOTAL CLASSROOMS</div>
                    <div class="text-[32px] leading-none font-bold text-[#0f172a]"><?= htmlspecialchars((string) ($stats['total_classrooms'] ?? 0)) ?></div>
                </div>
            </a>
            
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

