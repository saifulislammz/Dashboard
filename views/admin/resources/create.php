<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Distribute Resource to Group Classrooms</h1>
                <p class="mt-1 text-sm text-gray-500">Upload materials, files, assignments, or quizzes and distribute them in one click to all members.</p>
            </div>
            <a href="/admin/resources/index.php" class="text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                &larr; Back to Resources
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2 shadow-xs">
                <i class="ph ph-warning-circle text-lg text-red-600"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/admin/resources/create.php" method="POST" enctype="multipart/form-data" 
              x-data="{ 
                  resourceType: 'document',
                  selectAll: false,
                  toggleAll() {
                      this.selectAll = !this.selectAll;
                      document.querySelectorAll('.classroom-checkbox').forEach(cb => cb.checked = this.selectAll);
                  }
              }"
              class="space-y-6">

            <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">

            <!-- Main Resource Details -->
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
                        <input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                               placeholder="e.g. Chapter 3: Advanced Architecture & Query Optimization" 
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Description / Instructions
                        </label>
                        <textarea name="description" rows="3" 
                                  placeholder="Provide instructions, reading goals, or submission guidelines..." 
                                  class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Resource Type <span class="text-red-500">*</span>
                        </label>
                        <select name="resource_type" x-model="resourceType" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="document">📄 Document / File (PDF, Word, Zip, Sheet)</option>
                            <option value="video">🎥 Video Lecture (MP4 Upload or Cloud URL)</option>
                            <option value="assignment">📝 Assignment (Due Date, Marks & Submissions)</option>
                            <option value="quiz">❓ Quiz Exam (Select from LMS Quizzes)</option>
                            <option value="link">🔗 Web Reference Link</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Target Audience <span class="text-red-500">*</span>
                        </label>
                        <select name="target_audience" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="all">👥 All Members (Students & Teachers)</option>
                            <option value="students">🎓 Students Only</option>
                            <option value="teachers">👨‍🏫 Teachers Only (Lesson Plan / Teacher Guide)</option>
                        </select>
                    </div>
                </div>

                <!-- Conditional: File Attachment for Document, Video, Assignment -->
                <div x-show="['document', 'video', 'assignment'].includes(resourceType)" class="pt-3 border-t border-gray-50">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        File Attachment
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="file" name="attachment" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-200 rounded-xl cursor-pointer">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Allowed formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, MP4, MP3, Images (Max 50MB).</p>
                </div>

                <!-- Conditional: External URL for Link or Video -->
                <div x-show="['video', 'link', 'document'].includes(resourceType)" class="pt-3 border-t border-gray-50">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        <span x-text="resourceType === 'video' ? 'Video Streaming URL (YouTube, Vimeo, Google Drive, Zoom)' : 'External / Cloud Resource URL'"></span>
                    </label>
                    <input type="url" name="external_url" value="<?= htmlspecialchars($_POST['external_url'] ?? '') ?>" 
                           placeholder="https://..." 
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <!-- Conditional: Assignment Settings (Due Date, Max Points) -->
                <div x-show="resourceType === 'assignment'" class="pt-3 border-t border-gray-50 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Submission Due Date & Time
                        </label>
                        <input type="datetime-local" name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>" 
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Maximum Points / Marks
                        </label>
                        <input type="number" step="0.5" name="max_points" value="<?= htmlspecialchars($_POST['max_points'] ?? '100') ?>" 
                               placeholder="100" 
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <!-- Conditional: Quiz Selection -->
                <div x-show="resourceType === 'quiz'" class="pt-3 border-t border-gray-50">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Select Existing LMS Quiz <span class="text-red-500">*</span>
                    </label>
                    <select name="quiz_id" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">-- Choose Quiz --</option>
                        <?php foreach ($quizzes as $q): ?>
                            <option value="<?= (int)$q['id'] ?>" <?= ((int)($_POST['quiz_id'] ?? 0) === (int)$q['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($q['title']) ?> (<?= ucfirst($q['type']) ?>, <?= (int)$q['duration_minutes'] ?> mins, <?= (float)$q['total_marks'] ?> marks)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Target Group Classrooms Selection (Bulk Distribution Matrix) -->
            <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <i class="ph ph-chalkboard text-emerald-600 text-lg"></i>
                            Select Target Group Classroom(s)
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">The resource will be simultaneously distributed to all students and teachers in selected groups.</p>
                    </div>
                    <button type="button" @click="toggleAll()" 
                            class="text-xs font-bold text-primary hover:text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 transition-colors">
                        <span x-text="selectAll ? 'Deselect All' : 'Select All Classrooms'"></span>
                    </button>
                </div>

                <?php if (empty($classrooms)): ?>
                    <p class="text-sm text-gray-500 py-4">No active classrooms available. Please create classrooms first.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto pr-1">
                        <?php foreach ($classrooms as $c): ?>
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-primary/50 hover:bg-emerald-50/20 cursor-pointer transition-colors">
                                <input type="checkbox" name="classroom_ids[]" value="<?= (int)$c['id'] ?>" 
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
                                        <span><i class="ph ph-chalkboard-teacher"></i> <?= (int)$c['teacher_count'] ?> Teachers</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="status" value="published" id="statusPublished" checked class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="statusPublished" class="text-sm font-medium text-gray-700">Publish immediately to student & teacher dashboards</label>
                </div>
                <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-xs text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                    <i class="ph ph-paper-plane-tilt text-lg mr-2"></i>
                    Publish & Distribute to Group
                </button>
            </div>

        </form>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

