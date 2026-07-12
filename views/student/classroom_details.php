<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_student.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Classroom Details</h1>
            <a href="/student/classrooms.php" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                &larr; Back to My Classes
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header Section with Banner -->
            <div class="h-24 bg-gradient-to-r from-primary to-blue-500"></div>
            
            <div class="px-6 py-8 sm:p-10 -mt-12">
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100 inline-block mb-6 relative">
                    <div class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($classroom['class_name']) ?></div>
                    <div class="text-md text-gray-500 mt-1"><?= htmlspecialchars($classroom['class_title']) ?></div>
                </div>

                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Class Code</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 inline-block px-2 py-1 rounded"><?= htmlspecialchars($classroom['class_code']) ?></dd>
                    </div>
                    
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php if ($classroom['status'] === 'Active'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Assigned Teacher Name</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($classroom['teacher_name'] ?? 'N/A') ?></dd>
                    </div>

                    
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($classroom['created_at']))) ?></dd>
                    </div>
                </dl>
                
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="/student/sessions.php?classroom_id=<?= $classroom['id'] ?>" class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <svg class="mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        View Live Sessions
                    </a>
                    <p class="text-sm text-gray-500 italic">
                        Note: Students can view classroom details but cannot modify assignments. Contact the Administrator for changes.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
