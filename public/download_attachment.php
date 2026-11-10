<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/roles.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Repositories/NoticeRepository.php';
require_once __DIR__ . '/../src/Services/NoticeService.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    die('Invalid attachment ID.');
}

$repository = new \App\Repositories\NoticeRepository($db);
$service    = new \App\Services\NoticeService($repository);
$attachment = $repository->getAttachmentById($id);

if (!$attachment) {
    http_response_code(404);
    die('Attachment not found.');
}

// Ensure the user has permission to view the parent notice.
// If not admin, verify granular targeted access (Anti-IDOR)
global $auth;
if (!$auth->hasRole(ROLE_ADMIN)) {
    $userId = (int) $auth->getUserId();
    $role = 'user';
    if ($auth->hasRole(ROLE_STUDENT)) {
        $role = 'student';
    } elseif ($auth->hasRole(ROLE_TEACHER)) {
        $role = 'teacher';
    }

    $isAllowed = $service->canUserAccessNotice($userId, (int) $attachment['notice_id'], $role);

    if (!$isAllowed) {
        http_response_code(403);
        die('Access denied.');
    }
}

$filePath = realpath(__DIR__ . '/../storage/uploads/notices/' . $attachment['file_path']);

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    die('File not found on server.');
}

// Secure against directory traversal
if (strpos($filePath, realpath(__DIR__ . '/../storage/uploads/notices/')) !== 0) {
    http_response_code(403);
    die('Access denied.');
}

// Clear output buffer to avoid memory issues and corrupted files
if (ob_get_level()) {
    ob_end_clean();
}

// Send headers to force download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($attachment['file_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Stream the file
readfile($filePath);
exit;
