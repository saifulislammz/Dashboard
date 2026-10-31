<?php
/**
 * RBAC Security Audit Logs View
 */
declare(strict_types=1);

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto px-6 py-8 space-y-6">

        <!-- Breadcrumb / Back button -->
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
            <a href="/admin/roles/index.php" class="hover:text-emerald-600 flex items-center gap-1">
                <i class="ph ph-arrow-left"></i> Roles & Permissions
            </a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-800">RBAC Audit Trail</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Access Control Audit Trail</h1>
                <p class="text-sm text-[#64748b] mt-1">Immutable historical record of staff account creations, role assignments, and permission changes.</p>
            </div>
            <a href="/admin/roles/index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                <i class="ph ph-users text-lg"></i> Manage Roles
            </a>
        </div>

        <!-- Audit Table Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 text-xs uppercase tracking-wider text-gray-500 font-semibold border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Event Type</th>
                            <th class="px-6 py-4">Triggered By</th>
                            <th class="px-6 py-4">Target</th>
                            <th class="px-6 py-4">Event Details & Changes</th>
                            <th class="px-6 py-4">IP Address</th>
                            <th class="px-6 py-4">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-normal">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="ph ph-shield-check text-4xl block mx-auto mb-2 text-gray-300"></i>
                                    No administrative audit logs recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $eventColor = match($log['event_type']) {
                                    'STAFF_CREATED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'ROLE_CREATED' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'ROLE_UPDATED' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'ROLE_DELETED' => 'bg-red-100 text-red-800 border-red-200',
                                    'USER_ROLE_ASSIGNED' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200',
                                };
                                $details = !empty($log['details_json']) ? json_decode($log['details_json'], true) : [];
                                ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $eventColor ?>">
                                            <i class="ph ph-fingerprint text-xs"></i>
                                            <?= htmlspecialchars($log['event_type'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($log['actor_name'] ?: 'System / Admin', ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($log['actor_email'] ?: 'admin', ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-700">
                                        <?= htmlspecialchars(strtoupper($log['target_type']) . ' #' . $log['target_id'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600 font-mono">
                                        <?php if (!empty($details)): ?>
                                            <div class="bg-gray-50 rounded-lg p-2 max-w-sm overflow-x-auto text-[11px] border border-gray-100">
                                                <?= htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                        <?= htmlspecialchars($log['ip_address'] ?: '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        <?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

