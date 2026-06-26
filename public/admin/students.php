<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../src/Services/UserService.php';

requireRole(ROLE_ADMIN);

$repository = new \App\Repositories\UserRepository($db);
$service = new \App\Services\UserService($repository, $auth);

$action = $_GET['action'] ?? 'list';
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        if ($action === 'create') {
            $service->createUser($_POST, ROLE_STUDENT);
            $_SESSION['success_message'] = "Student created successfully.";
            header("Location: students.php");
            exit;
            
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $service->updateUser($id, $_POST);
            $_SESSION['success_message'] = "Student updated successfully.";
            header("Location: students.php");
            exit;
            
        } elseif ($action === 'status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = (int)($_POST['status'] ?? 0);
            $service->updateStatus($id, $status);
            $_SESSION['success_message'] = "Status updated.";
            header("Location: students.php");
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $service->deleteUser($id);
            $_SESSION['success_message'] = "Student deleted.";
            header("Location: students.php");
            exit;
        }

    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $errorMessage = "Email address already exists.";
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle GET for Listing
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

$paginated = $service->getPaginatedUsersByRole(ROLE_STUDENT, $page, $limit, $search);

$students = $paginated['data'];
$totalPages = $paginated['pages'];

require __DIR__ . '/../../views/admin/students.php';
