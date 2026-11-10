<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Classroom Resource</h1>
                <p class="mt-1 text-sm text-gray-500">Update resource details, deadlines, and group distribution mappings.</p>
            </div>
            <a href="/admin/resources/view.php?id=<?= (int)$resource['id'] ?>" class="text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                &larr; Back to Details
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2 shadow-xs">
                <i class="ph ph-warning-circle text-lg text-red-600"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/admin/resources/edit.php?id=<?= (int)$resource['id'] ?>" method="POST" enctype="multipart/form-data" 
              x-data="{ 
                  resourceType: '<?= htmlspecialchars($resource['resource_type']) ?>',
                  selectAll: false,
                  toggleAll() {
                      this.selectAll = !this.selectAll;
                      document.querySelectorAll('.classroom-checkbox').forEach(cb => cb.checked = this.selectAll);
                  }
              }"
              class="space-y-6">

            <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">

            <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-5">
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="ph ph-info text-emerald-600 text-lg"></i>
                    Resource Details
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Title / Topic Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? $resource['title']) ?>" 
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Description / Instructions
                        </label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"><?= htmlspecialchars($_POST['description'] ?? $resource['description']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Resource Type <span class="text-red-500">*</span>
                        </label>
                        <select name="resource_type" x-model="resourceType" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="document">📄 Document / File</option>
                            <option value="video">🎥 Video Lecture</option>
                            <option value="assignment">📝 Assignment</option>
                            <option value="quiz">❓ Quiz Exam</option>
                            <option value="link">🔗 Web Reference Link</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Target Audience <span class="text-red-500">*</span>
                        </label>
                        <select name="target_audience" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="all" <?= ($resource['target_audience'] === 'all') ? 'selected' : '' ?>>👥 All Members</option>
                            <option value="students" <?= ($resource['target_audience'] === 'students') ? 'selected' : '' ?>>🎓 Students Only</option>
                            <option value="teachers" <?= ($resource['target_audience'] === 'teachers') ? 'selected' : '' ?>>👨‍🏫 Teachers Only</option>
                        </select>
                    </div>
                </div>

                <!-- Existing & Replacement File -->
                <div x-show="['document', 'video', 'assignment'].includes(resourceType)" class="pt-3 border-t border-gray-50">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        File Attachment
                    </label>
                    <?php if (!empty($resource['file_path'])): ?>
                        <div class="mb-2 p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="ph ph-file-text text-lg text-emerald-600"></i>
                                <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($resource['original_name'] ?: 'Current file') ?></span>
                                <span class="text-xs text-gray-400">(<?= round(($resource['file_size'] ?? 0) / 1024 / 1024, 2) ?> MB)</span>
                            </div>
                            <a href="/resource/download.php?id=<?= (int)$resource['id'] ?>" class="text-xs font-bold text-primary hover:underline">
                                Download
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="attachment" 
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-200 rounded-xl cursor-pointer">
                    <p class="text-xs text-gray-400 mt-1">Leave blank to keep existing file. Uploading a new file will replace it.</p>
                </div>

                <!-- External URL -->
                <div x-show="['video', 'link', 'document'].includes(resourceType)" class="pt-3 border-t border-gray-50">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        External / Cloud URL
                    </label>
                    <input type="url" name="external_url" value="<?= htmlspecialchars($_POST['external_url'] ?? $resource['external_url'] ?? '') ?>" 
                           placeholder="https://..." 
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <!-- Assignment Settings -->
                <div x-show="resourceType === 'assignment'" class="pt-3 border-t border-gray-50 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Submission Due Date & Time
                        </label>
                        <input type="datetime-local" name="due_date" 
                               value="<?= !empty($resource['due_date']) ? date('Y-m-d\TH:i', strtotime($resource['due_date'])) : '' ?>" 
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Maximum Points / Marks
                        </label>
                        <input type="number" step="0.5" name="max_points" value="<?= htmlspecialchars((string)($resource['max_points'] ?? '100')) ?>" 
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <!-- Quiz Selection -->
                <div x-show="resourceType === 'quiz'" class="pt-3 border-t border-gray-50">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Select Existing LMS Quiz
                    </label>
                    <select name="quiz_id" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">-- Choose Quiz --</option>
                        <?php foreach ($quizzes as $q): ?>
                            <option value="<?= (int)$q['id'] ?>" <?= ((int)($resource['quiz_id'] ?? 0) === (int)$q['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($q['title']) ?> (<?= ucfirst($q['type']) ?>, <?= (int)$q['duration_minutes'] ?> mins)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Target Classrooms -->
            <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph ph-chalkboard text-emerald-600 text-lg"></i>
                        Distributed Group Classroom(s)
                    </h2>
                    <button type="button" @click="toggleAll()" class="text-xs font-bold text-primary hover:text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 transition-colors">
                        <span x-text="selectAll ? 'Deselect All' : 'Select All Classrooms'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto pr-1">
                    <?php foreach ($classrooms as $c): 
                        $isSelected = in_array((int)$c['id'], $selectedClassroomIds, true);
                    ?>
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border <?= $isSelected ? 'border-primary/60 bg-emerald-50/20' : 'border-gray-200' ?> hover:border-primary/50 cursor-pointer transition-colors">
                            <input type="checkbox" name="classroom_ids[]" value="<?= (int)$c['id'] ?>" <?= $isSelected ? 'checked' : '' ?>
                                   class="classroom-checkbox mt-1 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-bold bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">
                                        <?= htmlspecialchars($c['class_code']) ?>
                                    </span>
                                    <span class="text-sm font-bold text-gray-900 truncate">
                                        <?= htmlspecialchars($c['class_name']) ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-400 mt-1">
                                    <span><i class="ph ph-student"></i> <?= (int)$c['student_count'] ?> Students</span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="/admin/resources/view.php?id=<?= (int)$resource['id'] ?>" class="px-5 py-2.5 border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-xs text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

