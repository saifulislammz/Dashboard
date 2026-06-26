<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $status = (int)($_POST['status'] ?? 0); // 0 = active, 2 = inactive/banned in Delight Auth

            if (empty($name) || empty($email) || empty($password)) {
                throw new \Exception("All fields are required.");
            }
            if ($password !== $confirmPassword) {
                throw new \Exception("Passwords do not match.");
            }

            // Create user
            $userId = $auth->admin()->createUser($email, $password, $name);
            
            // Assign role
            $auth->admin()->addRoleForUserById($userId, ROLE_TEACHER);
            
            // Update status if inactive (Delight Auth uses status 0 for active, 2 for locked/banned)
            if ($status === 2) {
                $db->prepare("UPDATE users SET status = 2 WHERE id = ?")->execute([$userId]);
            }
            
            $_SESSION['success_message'] = "Teacher created successfully.";
            header("Location: teachers.php");
            exit;
            
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            
            if (empty($name) || !$id) {
                throw new \Exception("Invalid input.");
            }
            
            $db->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success_message'] = "Teacher updated successfully.";
            header("Location: teachers.php");
            exit;
            
        } elseif ($action === 'status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = (int)($_POST['status'] ?? 0);
            $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $id]);
            $_SESSION['success_message'] = "Status updated.";
            header("Location: teachers.php");
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $auth->admin()->deleteUserById($id);
            $_SESSION['success_message'] = "Teacher deleted.";
            header("Location: teachers.php");
            exit;
        }

    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $errorMessage = "Email address already exists.";
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle GET for Listing
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch teachers (where roles_mask includes ROLE_TEACHER)
$roleMask = ROLE_TEACHER;
$whereClause = "roles_mask & ? = ?";
$params = [$roleMask, $roleMask];

if (!empty($search)) {
    $whereClause .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Total count
$stmtCount = $db->prepare("SELECT COUNT(*) FROM users WHERE $whereClause");
$stmtCount->execute($params);
$totalTeachers = $stmtCount->fetchColumn();
$totalPages = ceil($totalTeachers / $limit);

// Fetch data
$stmt = $db->prepare("SELECT id, email, username as name, status, registered FROM users WHERE $whereClause ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key + 1, $val);
}
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../../views/admin/teachers.php';
