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
            <form action="edit.php" method="POST" enctype="multipart/form-data" class="space-y-6">
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

                <div class="space-y-2 border-t border-gray-200 pt-6">
                    <label class="block text-sm font-semibold text-gray-800">Add Attachments (Optional)</label>
                    <p class="text-xs text-gray-500 mb-3">Allowed types: .txt, .pdf, .jpg, .png, .doc. Max size: 10MB per file.</p>
                    
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file-edit" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 hover:border-primary transition-all duration-200">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p class="mb-1 text-sm text-gray-600"><span class="font-semibold text-primary">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-500">Any supported file (Max 10MB)</p>
                            </div>
                            <input id="dropzone-file-edit" name="attachments[]" type="file" multiple accept=".txt,.pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" onchange="document.getElementById('file-count-edit').textContent = this.files.length > 0 ? this.files.length + ' file(s) selected' : '';" />
                        </label>
                    </div>
                    <div id="file-count-edit" class="text-sm text-primary mt-2 font-medium"></div>
                    
                    <?php if (!empty($notice['attachments'])): ?>
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Existing Attachments</h4>
                            <ul class="space-y-2">
                                <?php foreach ($notice['attachments'] as $attachment): ?>
                                    <li class="flex items-center text-sm text-gray-600 bg-gray-50 p-2 rounded border border-gray-100">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        <?php echo e($attachment['file_name']); ?> (<?php echo round($attachment['file_size'] / 1024, 2); ?> KB)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
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
