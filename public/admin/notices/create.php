<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';

requireRole(ROLE_ADMIN);

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$oldValues = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        
        $audienceStudent = !empty($_POST['audience_student']);
        $audienceTeacher = !empty($_POST['audience_teacher']);
        
        $oldValues = [
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'audience_student' => $audienceStudent,
            'audience_teacher' => $audienceTeacher
        ];

        if (empty($title) || empty($content)) {
            throw new \Exception("Title and Description are required.");
        }

        if (!$audienceStudent && !$audienceTeacher) {
            throw new \Exception("Please select at least one target audience.");
        }

        $targetAudience = 'student';
        if ($audienceStudent && $audienceTeacher) {
            $targetAudience = 'both';
        } elseif ($audienceTeacher) {
            $targetAudience = 'teacher';
        }

        // Output escaping is done in views, content is stored raw text.
        // It's safe against SQLi because of PDO prepared statements.
        
        $stmt = $db->prepare("INSERT INTO notices (title, content, target_audience, status, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $title,
            $content,
            $targetAudience,
            $status,
            $auth->getUserId()
        ]);

        $_SESSION['success_message'] = "Notice created successfully.";
        header("Location: index.php");
        exit;

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

require __DIR__ . '/../../../views/admin/notices/create.php';
