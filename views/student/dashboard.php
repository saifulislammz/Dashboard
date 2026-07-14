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
                                
                                <?php if (!empty($notice['attachments'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Attachments:</h4>
                                        <div class="space-y-2">
                                            <?php foreach ($notice['attachments'] as $attachment): ?>
                                                <a href="/download_attachment.php?id=<?php echo $attachment['id']; ?>" class="flex items-center p-2 text-sm text-primary bg-primary/5 hover:bg-primary/10 rounded border border-primary/20 transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    <span class="truncate"><?php echo e($attachment['file_name']); ?></span>
                                                    <span class="ml-auto text-xs text-gray-500"><?php echo round($attachment['file_size'] / 1024, 2); ?> KB</span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
