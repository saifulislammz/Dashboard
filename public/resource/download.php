<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/ClassroomResourceRepository.php';

requireLogin();

global $db, $auth;
$userId = (int) $auth->getUserId();
$rolesMask = 0;
if ($auth->hasRole(ROLE_ADMIN))   $rolesMask |= ROLE_ADMIN;
if ($auth->hasRole(ROLE_TEACHER)) $rolesMask |= ROLE_TEACHER;
if ($auth->hasRole(ROLE_STUDENT)) $rolesMask |= ROLE_STUDENT;

$id = (int) ($_GET['id'] ?? 0);
$type = strtolower(trim($_GET['type'] ?? 'resource'));

if ($id <= 0) {
    http_response_code(400);
    die('Invalid request identifier.');
}

$repository = new \App\Repositories\ClassroomResourceRepository($db);

if ($type === 'submission') {
    // Student assignment submission download
    $stmt = $db->prepare("SELECT cas.*, cr.created_by AS resource_creator, cr.id AS resource_id 
                          FROM classroom_assignment_submissions cas
                          INNER JOIN classroom_resources cr ON cas.resource_id = cr.id
                          WHERE cas.id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub || empty($sub['file_path'])) {
        http_response_code(404);
        die('Submission file not found.');
    }

    // Access check: Admin, Teacher in classroom, or Student owner
    $isOwner = ((int)$sub['student_id'] === $userId);
    $canAccess = $isOwner || $repository->canUserAccessResource((int)$sub['resource_id'], $userId, $rolesMask);

    if (!$canAccess) {
        http_response_code(403);
        die('Access denied.');
    }

    $baseDir = realpath(__DIR__ . '/../../storage/uploads/assignment_submissions');
    $filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $sub['file_path']);
    $fileName = $sub['original_name'] ?: 'submission_' . $sub['id'];

} else {
    // Classroom resource file download
    $resource = $repository->findById($id);

    if (!$resource || empty($resource['file_path'])) {
        http_response_code(404);
        die('Classroom resource file not found.');
    }

    if (!$repository->canUserAccessResource($id, $userId, $rolesMask)) {
        http_response_code(403);
        die('Access denied: You do not have permission to download this classroom resource.');
    }

    $baseDir = realpath(__DIR__ . '/../../storage/uploads/classroom_resources');
    $filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $resource['file_path']);
    $fileName = $resource['original_name'] ?: 'resource_' . $resource['id'];
}

// Security: Prevent Directory Traversal
if (!$baseDir || !$filePath || !file_exists($filePath) || strpos($filePath, $baseDir) !== 0) {
    http_response_code(404);
    die('File not found or illegal file path.');
}

// Clear any buffered output to avoid corrupt binary transfers and high memory spikes
while (ob_get_level() > 0) {
    ob_end_clean();
}

$mime = mime_content_type($filePath) ?: 'application/octet-stream';
$escapedName = str_replace('"', '', basename($fileName));

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $escapedName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: private, must-revalidate, max-age=86400');
header('Pragma: private');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;

