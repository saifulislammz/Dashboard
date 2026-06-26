<?php
$pageTitle = 'Student Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_student.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Hello Student</h1>
        </div>
        
        <?php if (!empty($notices)): ?>
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Notice Board</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($notices as $notice): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                            <div class="p-6 flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo e($notice['title']); ?></h3>
                                <p class="text-gray-600 text-sm whitespace-pre-line"><?php echo e($notice['content']); ?></p>
                            </div>
                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 text-xs text-gray-500">
                                Published: <?php echo date('d F Y', strtotime($notice['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
            <p class="text-gray-600">Welcome Student Dashboard</p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
