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

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch Notice
$stmt = $db->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->execute([$id]);
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notice) {
    $_SESSION['error_message'] = "Notice not found.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        
        $audienceStudent = !empty($_POST['audience_student']);
        $audienceTeacher = !empty($_POST['audience_teacher']);
        
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
        
        $stmt = $db->prepare("UPDATE notices SET title = ?, content = ?, target_audience = ?, status = ? WHERE id = ?");
        $stmt->execute([
            $title,
            $content,
            $targetAudience,
            $status,
            $id
        ]);

        $_SESSION['success_message'] = "Notice updated successfully.";
        header("Location: index.php");
        exit;

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        // Fallback to post values
        $notice['title'] = $_POST['title'] ?? '';
        $notice['content'] = $_POST['content'] ?? '';
        $notice['status'] = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $notice['target_audience'] = 'student';
        if (!empty($_POST['audience_student']) && !empty($_POST['audience_teacher'])) {
            $notice['target_audience'] = 'both';
        } elseif (!empty($_POST['audience_teacher'])) {
            $notice['target_audience'] = 'teacher';
        }
    }
}

require __DIR__ . '/../../../views/admin/notices/edit.php';
