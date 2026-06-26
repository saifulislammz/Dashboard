<?php
$pageTitle = 'Edit Notice';
$activeMenu = 'notices_manage';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$title = $notice['title'] ?? '';
$content = $notice['content'] ?? '';
$status = $notice['status'] ?? 'active';

$targetAudience = $notice['target_audience'] ?? 'student';
$audienceStudent = ($targetAudience === 'student' || $targetAudience === 'both');
$audienceTeacher = ($targetAudience === 'teacher' || $targetAudience === 'both');
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Edit Notice</h1>
            <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                &larr; Back to Notices
            </a>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
                <p class="text-sm text-red-700"><?php echo e($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6">
            <form action="edit.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)($notice['id'] ?? 0); ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notice Title</label>
                    <input type="text" name="title" required value="<?php echo e($title); ?>" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notice Description</label>
                    <textarea name="content" required rows="6" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"><?php echo e($content); ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Target Audience -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="audience_student" value="1" <?php echo $audienceStudent ? 'checked' : ''; ?> class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4">
                                <span class="ml-2 text-sm text-gray-700">Students</span>
                            </label>
                            <br>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="audience_teacher" value="1" <?php echo $audienceTeacher ? 'checked' : ''; ?> class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4">
                                <span class="ml-2 text-sm text-gray-700">Teachers</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="active" <?php echo $status === 'active' ? 'checked' : ''; ?> class="border-gray-300 text-primary focus:ring-primary h-4 w-4">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                            <br>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="inactive" <?php echo $status === 'inactive' ? 'checked' : ''; ?> class="border-gray-300 text-primary focus:ring-primary h-4 w-4">
                                <span class="ml-2 text-sm text-gray-700">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200 gap-3">
                    <a href="index.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">Cancel</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none">Update Notice</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
