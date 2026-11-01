<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\RolePermissionService;
use Delight\Auth\Auth;
use PDO;

class RoleController
{
    private PDO $db;
    private Auth $auth;
    private RolePermissionService $service;

    public function __construct(PDO $db, Auth $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->service = new RolePermissionService($db);
    }

    /**
     * Display staff members & roles management dashboard.
     *
     * @return void
     */
    public function index(): void
    {
        requirePermission('roles.view');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $search = trim((string) ($_GET['search'] ?? ''));

        $roles = $this->service->getAllRoles();
        $staff = $this->service->getStaffList($limit, $offset, $search);
        $totalStaff = $this->service->countStaff($search);
        $totalPages = (int) ceil($totalStaff / $limit);

        $currentUserIsSuperAdmin = isSuperAdmin();

        $activeTab = $_GET['tab'] ?? 'staff';
        $activeMenu = 'roles_manage';

        require __DIR__ . '/../../../views/admin/roles/index.php';
    }

    /**
     * Show form to create a new custom role with permissions matrix.
     *
     * @return void
     */
    public function create(): void
    {
        requirePermission('roles.create');

        $modules = $this->service->getPermissionMatrix();
        $activeMenu = 'roles_create';

        require __DIR__ . '/../../../views/admin/roles/create.php';
    }

    /**
     * Handle POST submission to create a new role.
     *
     * @return void
     */
    public function store(): void
    {
        requirePermission('roles.create');
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $permissionIds = array_map('intval', (array) ($_POST['permissions'] ?? []));

        $actorId = (int) $this->auth->getUserId();

        try {
            $roleId = $this->service->createRole($name, $slug, $description, $permissionIds, $actorId);
            $_SESSION['flash_success'] = "Role '{$name}' was created successfully.";
            header("Location: /admin/roles/index.php?tab=roles");
            exit();
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header("Location: /admin/roles/create.php");
            exit();
        }
    }

    /**
     * Show form to edit an existing role and its permissions.
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        requirePermission('roles.edit');

        try {
            $role = $this->service->getRoleWithPermissions($id);
            $modules = $this->service->getPermissionMatrix();
            $activeMenu = 'roles_manage';

            require __DIR__ . '/../../../views/admin/roles/edit.php';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /admin/roles/index.php?tab=roles");
            exit();
        }
    }

    /**
     * Handle POST submission to update a role.
     *
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        requirePermission('roles.edit');
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $permissionIds = array_map('intval', (array) ($_POST['permissions'] ?? []));

        $actorId = (int) $this->auth->getUserId();

        try {
            $this->service->updateRole($id, $name, $description, $permissionIds, $actorId);
            $_SESSION['flash_success'] = "Role updated successfully.";
            header("Location: /admin/roles/index.php?tab=roles");
            exit();
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /admin/roles/edit.php?id=" . $id);
            exit();
        }
    }

    /**
     * Handle POST submission to delete a role.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        requirePermission('roles.delete');
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $actorId = (int) $this->auth->getUserId();

        try {
            $this->service->deleteRole($id, $actorId);
            $_SESSION['flash_success'] = "Role removed successfully.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header("Location: /admin/roles/index.php?tab=roles");
        exit();
    }

    /**
     * Handle POST submission to register a new staff/admin member.
     *
     * @return void
     */
    public function storeStaff(): void
    {
        requirePermission('roles.assign');
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $actorId = (int) $this->auth->getUserId();
        $actorIsSuperAdmin = isSuperAdmin();

        try {
            $this->service->createStaffAccount($email, $username, $password, $roleId, $actorId, $actorIsSuperAdmin, $this->auth);
            $_SESSION['flash_success'] = "New staff member account created successfully for {$username}.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header("Location: /admin/roles/index.php?tab=staff");
        exit();
    }

    /**
     * Handle POST submission to reassign role of an existing staff member.
     *
     * @return void
     */
    public function assignRole(): void
    {
        requirePermission('roles.assign');
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $newRoleId = (int) ($_POST['role_id'] ?? 0);

        $actorId = (int) $this->auth->getUserId();
        $actorIsSuperAdmin = isSuperAdmin();

        try {
            $this->service->assignRoleToStaff($targetUserId, $newRoleId, $actorId, $actorIsSuperAdmin);
            $_SESSION['flash_success'] = "Staff role updated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header("Location: /admin/roles/index.php?tab=staff");
        exit();
    }

    /**
     * Display RBAC Audit Log trail.
     *
     * @return void
     */
    public function auditLogs(): void
    {
        requirePermission('roles.view');

        $logs = $this->service->getAuditLogs(60);
        $activeMenu = 'roles_audit';

        require __DIR__ . '/../../../views/admin/roles/audit_logs.php';
    }
}

