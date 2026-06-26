<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Classroom: <?= htmlspecialchars($classroom['class_code']) ?></h1>
            <a href="/admin/classrooms/index.php" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                &larr; Back to Classrooms
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form action="" method="POST" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Class Code</label>
                        <div class="mt-1">
                            <input type="text" value="<?= htmlspecialchars($classroom['class_code']) ?>" disabled class="shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="class_name" class="block text-sm font-medium text-gray-700">Class Name <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="text" name="class_name" id="class_name" value="<?= htmlspecialchars($classroom['class_name']) ?>" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="class_title" class="block text-sm font-medium text-gray-700">Class Title <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="text" name="class_title" id="class_title" value="<?= htmlspecialchars($classroom['class_title']) ?>" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Assigned Teacher</label>
                        <div class="mt-1">
                            <input type="text" value="<?= htmlspecialchars($classroom['teacher_name'] . ' (' . $classroom['teacher_email'] . ')') ?>" disabled class="shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Teacher assignment cannot be modified.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Assigned Student</label>
                        <div class="mt-1">
                            <input type="text" value="<?= htmlspecialchars($classroom['student_name'] . ' (' . $classroom['student_email'] . ')') ?>" disabled class="shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Student assignment cannot be modified.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="mt-1">
                            <select id="status" name="status" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="Active" <?= $classroom['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $classroom['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="w-full sm:w-auto">
                        <a href="/admin/sessions/index.php?classroom_id=<?= $classroom['id'] ?>" class="inline-flex justify-center w-full sm:w-auto py-2 px-4 border border-primary shadow-sm text-sm font-medium rounded-md text-primary bg-white hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            <svg class="mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Manage Live Sessions
                        </a>
                    </div>
                    <div class="flex justify-end gap-3 w-full sm:w-auto">
                        <a href="/admin/classrooms/index.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Update Classroom
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
