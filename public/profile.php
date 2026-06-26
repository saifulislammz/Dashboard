<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/roles.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';

requireLogin();

// User Info for display
$email = $auth->getEmail();
$username = $auth->getUsername();
$statusMap = [
    \Delight\Auth\Status::NORMAL => 'Active',
    \Delight\Auth\Status::ARCHIVED => 'Archived',
    \Delight\Auth\Status::BANNED => 'Banned',
    \Delight\Auth\Status::LOCKED => 'Locked',
    \Delight\Auth\Status::PENDING_REVIEW => 'Pending Review',
    \Delight\Auth\Status::SUSPENDED => 'Suspended',
];

$stmt = $db->prepare('SELECT status FROM users WHERE id = ?');
$stmt->execute([$auth->getUserId()]);
$statusInt = (int)$stmt->fetchColumn();
$statusText = $statusMap[$statusInt] ?? 'Unknown';

$role = 'Student';
if ($auth->hasRole(ROLE_ADMIN) || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)) {
    $role = 'Administrator';
} elseif ($auth->hasRole(ROLE_TEACHER)) {
    $role = 'Teacher';
}

require __DIR__ . '/../views/profile.php';
