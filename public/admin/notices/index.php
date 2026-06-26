<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        if ($action === 'status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
            $db->prepare("UPDATE notices SET status = ? WHERE id = ?")->execute([$status, $id]);
            $_SESSION['success_message'] = "Notice status updated.";
            header("Location: index.php");
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("DELETE FROM notices WHERE id = ?")->execute([$id]);
            $_SESSION['success_message'] = "Notice deleted.";
            header("Location: index.php");
            exit;
            
        } elseif ($action === 'duplicate') {
            $id = (int)($_POST['id'] ?? 0);
            // Fetch original notice
            $stmt = $db->prepare("SELECT * FROM notices WHERE id = ?");
            $stmt->execute([$id]);
            $notice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($notice) {
                $newTitle = $notice['title'] . ' (Copy)';
                $insertStmt = $db->prepare("INSERT INTO notices (title, content, target_audience, status, created_by) VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $newTitle,
                    $notice['content'],
                    $notice['target_audience'],
                    'inactive', // Default to inactive when duplicating
                    $auth->getUserId()
                ]);
                $_SESSION['success_message'] = "Notice duplicated.";
            } else {
                $_SESSION['error_message'] = "Notice not found.";
            }
            header("Location: index.php");
            exit;
        }

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle GET for Listing
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = "1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (n.title LIKE ? OR n.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Total count
$stmtCount = $db->prepare("SELECT COUNT(*) FROM notices n WHERE $whereClause");
$stmtCount->execute($params);
$totalNotices = $stmtCount->fetchColumn();
$totalPages = ceil($totalNotices / $limit);

// Fetch data
$stmt = $db->prepare("
    SELECT n.*, u.username as creator_name 
    FROM notices n 
    LEFT JOIN users u ON n.created_by = u.id 
    WHERE $whereClause 
    ORDER BY n.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key + 1, $val);
}
$stmt->execute();
$notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../../../views/admin/notices/index.php';
