<?php
/**
 * Edit Role View with Pre-checked Permissions Matrix
 */
declare(strict_types=1);

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$csrfToken = generateCsrfToken();
$assignedIds = $role['assigned_permission_ids'] ?? [];
$isSystem = (int)($role['is_system'] ?? 0) === 1;

// Module display metadata
$moduleMeta = [
    'analytics' => ['label' => 'Dashboard Analytics', 'icon' => 'ph-presentation-chart', 'color' => 'emerald'],
    'users' => ['label' => 'Student & Teacher Management', 'icon' => 'ph-users', 'color' => 'blue'],
    'classrooms' => ['label' => 'Classroom Management', 'icon' => 'ph-chalkboard', 'color' => 'indigo'],
    'notices' => ['label' => 'Notice Board', 'icon' => 'ph-megaphone', 'color' => 'amber'],
    'sessions' => ['label' => 'Sessions & Meetings', 'icon' => 'ph-video-camera', 'color' => 'rose'],
    'attendance' => ['label' => 'Attendance System', 'icon' => 'ph-calendar-check', 'color' => 'cyan'],
    'invoices' => ['label' => 'Invoice Management', 'icon' => 'ph-receipt', 'color' => 'emerald'],
    'quiz' => ['label' => 'Quiz & Exam System', 'icon' => 'ph-question', 'color' => 'purple'],
    'settings' => ['label' => 'Settings & Integrations', 'icon' => 'ph-gear-six', 'color' => 'slate'],
    'roles' => ['label' => 'Role & Permission Controls', 'icon' => 'ph-fingerprint', 'color' => 'red'],
];
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1400px] mx-auto px-6 py-8 space-y-6">

        <!-- Breadcrumb / Back button -->
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
            <a href="/admin/roles/index.php?tab=roles" class="hover:text-emerald-600 flex items-center gap-1">
                <i class="ph ph-arrow-left"></i> Roles & Permissions
            </a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-800">Edit Role: <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Edit Role: <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if ($isSystem): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            <i class="ph ph-lock text-xs"></i> System Role
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-[#64748b] mt-1">Modify role information and update enabled actions for this role.</p>
            </div>
        </div>

        <?php if ($flashError): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-red-800">
                <i class="ph ph-warning-circle text-lg text-red-600 shrink-0"></i>
                <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="/admin/roles/edit.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int)$role['id'] ?>">

            <!-- Role Information Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph ph-identification-badge text-primary text-xl"></i> Role Details
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Role Display Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>"
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Role Identifier (Slug)</label>
                        <input type="text" disabled
                               value="<?= htmlspecialchars($role['slug'], ENT_QUOTES, 'UTF-8') ?>"
                               class="w-full px-4 py-2.5 text-sm font-mono border border-gray-200 rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed">
                        <p class="text-[11px] text-gray-400 mt-1">Slugs are immutable to prevent breaking codebase permission checks.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Describe the responsibilities and scope of this role..."
                                  class="w-full px-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none"><?= htmlspecialchars($role['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Permission Matrix Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph ph-shield-check text-primary text-xl"></i> Module Permissions Matrix
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Toggle individual capabilities for members with this role.</p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 cursor-pointer shadow-sm hover:bg-gray-50 transition-colors">
                        <input type="checkbox" id="masterSelectAll" onchange="toggleAllPermissions(this.checked)" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                        <span>Select All Permissions</span>
                    </label>
                </div>
            </div>

            <!-- Permission Modules Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($modules as $moduleKey => $perms): ?>
                    <?php
                    $meta = $moduleMeta[$moduleKey] ?? [
                        'label' => ucfirst($moduleKey),
                        'icon' => 'ph-app-window',
                        'color' => 'emerald'
                    ];
                    ?>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between">
                        <!-- Module Card Header -->
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-<?= $meta['color'] ?>-50 text-<?= $meta['color'] ?>-600 flex items-center justify-center shrink-0">
                                    <i class="ph <?= $meta['icon'] ?> text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <span class="text-[11px] text-gray-400 font-mono"><?= count($perms) ?> actions</span>
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg cursor-pointer hover:bg-emerald-100 transition-colors">
                                <input type="checkbox" onchange="toggleModulePermissions('<?= $moduleKey ?>', this.checked)" class="module-master-<?= $moduleKey ?> w-3.5 h-3.5 text-emerald-600 rounded border-gray-300">
                                <span>Toggle All</span>
                            </label>
                        </div>

                        <!-- Module Actions Checkbox List -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-3">
                            <?php foreach ($perms as $p): ?>
                                <?php
                                $isChecked = in_array((int)$p['id'], $assignedIds, true);
                                ?>
                                <label class="flex items-start gap-2.5 p-2 rounded-xl hover:bg-gray-50/75 cursor-pointer border border-transparent hover:border-gray-100 transition-colors group">
                                    <input type="checkbox" name="permissions[]" value="<?= (int)$p['id'] ?>" <?= $isChecked ? 'checked' : '' ?>
                                           class="perm-checkbox perm-module-<?= $moduleKey ?> mt-0.5 w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                    <div class="text-xs">
                                        <div class="font-semibold text-gray-800 group-hover:text-emerald-700 transition-colors">
                                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-[10px] text-gray-400 line-clamp-1 mt-0.5">
                                            <?= htmlspecialchars($p['description'] ?: $p['slug'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sticky Bottom Action Bar -->
            <div class="sticky bottom-4 bg-white/90 backdrop-blur-md rounded-2xl border border-gray-200 p-4 shadow-lg flex items-center justify-between z-20">
                <a href="/admin/roles/index.php?tab=roles" class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-emerald-700 transition-colors shadow-md">
                    <i class="ph ph-floppy-disk text-lg"></i> Update Role & Permissions
                </button>
            </div>
        </form>

    </div>
</main>

<script nonce="<?php echo csp_nonce(); ?>">
function toggleAllPermissions(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    document.querySelectorAll('[class*="module-master-"]').forEach(cb => {
        cb.checked = checked;
    });
}

function toggleModulePermissions(moduleKey, checked) {
    document.querySelectorAll('.perm-module-' + moduleKey).forEach(cb => {
        cb.checked = checked;
    });
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

