<?php
/**
 * Role & Permission Management Dashboard
 * Dual Tab View: Staff Accounts & Custom Roles
 */
declare(strict_types=1);

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$activeTab = $activeTab ?? 'staff';
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$csrfToken = generateCsrfToken();
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto px-6 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Role & Permission Management</h1>
                <p class="text-sm text-[#64748b] mt-1">Configure staff access, create custom roles, and fine-tune granular module permissions.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/roles/audit.php" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                    <i class="ph ph-shield-check text-lg text-emerald-600"></i> Audit Logs
                </a>
                <?php if ($activeTab === 'staff'): ?>
                    <?php if (hasPermission('roles.assign')): ?>
                        <button type="button" data-modal-open="createStaffModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                            <i class="ph ph-user-plus text-lg"></i> Add Staff Account
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (hasPermission('roles.create')): ?>
                        <a href="/admin/roles/create.php" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                            <i class="ph ph-plus-circle text-lg"></i> Create Custom Role
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Flash Notifications -->
        <?php if ($flashSuccess): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-emerald-800">
                <i class="ph ph-check-circle text-lg text-emerald-600 shrink-0"></i>
                <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-red-800">
                <i class="ph ph-warning-circle text-lg text-red-600 shrink-0"></i>
                <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Roles</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1"><?= count($roles) ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="ph ph-fingerprint text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Staff & Admins</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1"><?= $totalStaff ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="ph ph-users-three text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Custom Roles</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1"><?= count(array_filter($roles, fn($r) => (int)$r['is_system'] === 0)) ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i class="ph ph-tree-structure text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Access Modules</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1">10 Modules</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="ph ph-squares-four text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="border-b border-gray-200">
            <nav class="flex gap-6 -mb-px">
                <a href="/admin/roles/index.php?tab=staff"
                   class="pb-3 text-sm font-semibold border-b-2 transition-colors <?= $activeTab === 'staff' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    <i class="ph ph-users text-base mr-1.5 align-middle"></i> Staff & Moderators (<?= $totalStaff ?>)
                </a>
                <a href="/admin/roles/index.php?tab=roles"
                   class="pb-3 text-sm font-semibold border-b-2 transition-colors <?= $activeTab === 'roles' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    <i class="ph ph-shield-check text-base mr-1.5 align-middle"></i> Roles & Permissions (<?= count($roles) ?>)
                </a>
            </nav>
        </div>

        <?php if ($activeTab === 'staff'): ?>
            <!-- TAB 1: STAFF ACCOUNTS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <!-- Search & Filters -->
                <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-3">
                    <form method="GET" action="/admin/roles/index.php" class="w-full sm:w-80">
                        <input type="hidden" name="tab" value="staff">
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-3 top-3 text-gray-400"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Search by name or email..."
                                   class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                    </form>
                </div>

                <!-- Staff Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/75 text-xs uppercase tracking-wider text-gray-500 font-semibold border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Staff Member</th>
                                <th class="px-6 py-4">Assigned Role</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Joined Date</th>
                                <th class="px-6 py-4">Last Login</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-normal">
                            <?php if (empty($staff)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        <i class="ph ph-user-circle-gear text-4xl block mx-auto mb-2 text-gray-300"></i>
                                        No staff accounts found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($staff as $user): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-sm shrink-0">
                                                    <?= strtoupper(substr($user['username'] ?: $user['email'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($user['username'] ?: 'No Username', ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $isUserSuperAdmin = ((int)($user['roles_mask'] ?? 0) & 262144) || ($user['role_slug'] === 'super_admin');
                                            $badgeClass = $isUserSuperAdmin 
                                                ? 'bg-amber-100 text-amber-800 border-amber-200' 
                                                : ($user['role_slug'] === 'admin' 
                                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-200' 
                                                    : 'bg-purple-100 text-purple-800 border-purple-200');
                                            $roleDisplayName = $user['role_name'] ?: ($isUserSuperAdmin ? 'Super Admin' : 'Admin');
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $badgeClass ?>">
                                                <i class="ph ph-shield text-sm"></i>
                                                <?= htmlspecialchars($roleDisplayName, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ((int)$user['status'] === 0): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            <?= !empty($user['registered']) ? date('M d, Y', (int)$user['registered']) : 'N/A' ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            <?= !empty($user['last_login']) ? date('M d, Y H:i', (int)$user['last_login']) : 'Never' ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <?php if (hasPermission('roles.assign')): ?>
                                                <button onclick="openAssignModal(<?= (int)$user['id'] ?>, '<?= htmlspecialchars($user['username'] ?: $user['email'], ENT_QUOTES, 'UTF-8') ?>', <?= (int)($user['role_id'] ?? 0) ?>)"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-emerald-600 transition-colors shadow-sm">
                                                    <i class="ph ph-arrows-clockwise text-sm"></i> Change Role
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                        <div class="text-xs text-gray-500">Showing page <?= $page ?> of <?= $totalPages ?></div>
                        <div class="flex gap-2">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="/admin/roles/index.php?tab=staff&page=<?= $i ?>&search=<?= urlencode($search) ?>"
                                   class="px-3 py-1 text-xs rounded-lg font-medium border <?= $i === $page ? 'bg-primary text-white border-primary' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- TAB 2: ROLES & PERMISSIONS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/75 text-xs uppercase tracking-wider text-gray-500 font-semibold border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Role Name & Slug</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4">Staff Assigned</th>
                                <th class="px-6 py-4">Granted Permissions</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-normal">
                            <?php foreach ($roles as $role): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-xs font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($role['slug'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 max-w-xs">
                                        <?= htmlspecialchars($role['description'] ?: 'No description provided.', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ((int)$role['is_system'] === 1): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                <i class="ph ph-lock text-xs"></i> System Role
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="ph ph-paint-brush-broad text-xs"></i> Custom
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-gray-800"><?= (int)($role['users_count'] ?? 0) ?></span> staff
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700">
                                            <?= (int)($role['permissions_count'] ?? 0) ?> permissions
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <?php if (hasPermission('roles.edit')): ?>
                                                <a href="/admin/roles/edit.php?id=<?= (int)$role['id'] ?>"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-gray-200 rounded-lg hover:bg-slate-50 hover:text-emerald-600 transition-colors shadow-sm">
                                                    <i class="ph ph-pencil-simple text-sm"></i> Edit Permissions
                                                </a>
                                            <?php endif; ?>

                                            <?php if ((int)$role['is_system'] === 0 && hasPermission('roles.delete')): ?>
                                                <button onclick="confirmDeleteRole(<?= (int)$role['id'] ?>, '<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>', <?= (int)($role['users_count'] ?? 0) ?>)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors">
                                                    <i class="ph ph-trash text-sm"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- MODAL 1: ADD NEW STAFF ACCOUNT -->
<div id="createStaffModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform transition-all">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="ph ph-user-plus text-primary text-xl"></i> Create Staff Member
            </h3>
            <button type="button" data-modal-close="createStaffModal" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <form action="/admin/roles/create_staff.php" method="POST" class="space-y-4 mt-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Full Name / Username</label>
                <input type="text" name="username" required placeholder="e.g. sakib_moderator"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Email Address</label>
                <input type="email" name="email" required placeholder="staff@school.com"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Temporary Password</label>
                <input type="password" name="password" required minlength="8" placeholder="Min 8 characters"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Assign Role</label>
                <select name="role_id" required class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none bg-white">
                    <?php foreach ($roles as $r): ?>
                        <?php if ($r['slug'] === 'super_admin' && !$currentUserIsSuperAdmin) continue; ?>
                        <option value="<?= (int)$r['id'] ?>" <?= $r['slug'] === 'moderator' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $r['is_system'] ? 'System' : 'Custom' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Permissions configured for this role will apply immediately.</p>
            </div>

            <div class="pt-3 border-t border-gray-100 flex gap-3 justify-end">
                <button type="button" type="button" data-modal-close="createStaffModal" class="px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: ASSIGN / CHANGE ROLE -->
<div id="assignRoleModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform transition-all">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="ph ph-arrows-clockwise text-primary text-xl"></i> Change Staff Role
            </h3>
            <button type="button" data-modal-close="assignRoleModal" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <form action="/admin/roles/assign.php" method="POST" class="space-y-4 mt-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="assignUserId" name="user_id" value="">

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Staff Member</label>
                <input type="text" id="assignUserName" disabled class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-600 font-semibold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Select New Role</label>
                <select id="assignRoleId" name="role_id" required class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none bg-white">
                    <?php foreach ($roles as $r): ?>
                        <?php if ($r['slug'] === 'super_admin' && !$currentUserIsSuperAdmin) continue; ?>
                        <option value="<?= (int)$r['id'] ?>">
                            <?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pt-3 border-t border-gray-100 flex gap-3 justify-end">
                <button type="button" type="button" data-modal-close="assignRoleModal" class="px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                    Update Role
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: DELETE ROLE CONFIRMATION -->
<div id="deleteRoleModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 text-center">
        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="ph ph-trash text-3xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Custom Role?</h3>
        <p class="text-sm text-gray-500 mb-6" id="deleteRoleMessage">
            Are you sure you want to delete this custom role? This action cannot be undone.
        </p>

        <form id="deleteRoleForm" action="/admin/roles/delete.php" method="POST" class="flex gap-3 justify-center">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="deleteRoleId" name="id" value="">

            <button type="button" type="button" data-modal-close="deleteRoleModal" class="px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                Cancel
            </button>
            <button type="submit" id="confirmDeleteBtn" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors shadow-sm">
                Delete Role
            </button>
        </form>
    </div>
</div>

<script nonce="<?php echo csp_nonce(); ?>">
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

window.openAssignModal = function(userId, username, roleId) {
    document.getElementById('assignUserId').value = userId;
    document.getElementById('assignUserName').value = username;
    if (roleId) {
        document.getElementById('assignRoleId').value = roleId;
    }
    openModal('assignRoleModal');
}

window.confirmDeleteRole = function(roleId, roleName, usersCount) {
    if (usersCount > 0) {
        alert("Cannot delete role '" + roleName + "' because " + usersCount + " staff member(s) are currently assigned to it. Reassign them first.");
        return;
    }
    document.getElementById('deleteRoleId').value = roleId;
    document.getElementById('deleteRoleMessage').innerText = "Are you sure you want to delete role '" + roleName + "'?";
    openModal('deleteRoleModal');
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

