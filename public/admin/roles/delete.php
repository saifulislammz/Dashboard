<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/RoleController.php';

requirePermission('roles.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /admin/roles/index.php?tab=roles");
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$controller = new \App\Controllers\Admin\RoleController($db, $auth);
$controller->delete($id);

