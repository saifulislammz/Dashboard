<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../src/config/security.php';
require_once __DIR__ . '/../../../../src/config/database.php';
require_once __DIR__ . '/../../../../src/config/roles.php';
require_once __DIR__ . '/../../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../../src/Repositories/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireLogin();
    requireRole(ROLE_ADMIN);

    $term  = trim((string) ($_GET['q'] ?? $_GET['term'] ?? ''));
    $role  = trim((string) ($_GET['role'] ?? ''));
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

    $userRepo = new \App\Repositories\UserRepository($db);
    $users = $userRepo->searchActiveUsers($term, !empty($role) && $role !== 'all' ? $role : null, $limit);

    echo json_encode([
        'success' => true,
        'count'   => count($users),
        'data'    => $users
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

