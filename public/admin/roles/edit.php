<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/RoleController.php';

requirePermission('roles.edit');

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    header("Location: /admin/roles/index.php?tab=roles");
    exit();
}

$controller = new \App\Controllers\Admin\RoleController($db, $auth);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->update($id);
} else {
    $controller->edit($id);
}

