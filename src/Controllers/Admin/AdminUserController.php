<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\UserService;
use App\Services\UserEmailManagementService;
use App\Services\Mail\PhpMailerService;
use App\Repositories\UserRepository;

/**
 * AdminUserController — Thin Controller
 * Handles admin CRUD for both Students and Teachers.
 * Business logic stays in UserService / UserEmailManagementService; DB access stays in UserRepository.
 * No SQL, no business logic here.
 */
class AdminUserController
{
    private UserService                  $service;
    private int                          $roleMask;
    private string                       $userType;   // 'Student' | 'Teacher'
    private string                       $redirectUrl;
    private ?UserEmailManagementService  $emailService;

    public function __construct(
        UserService $service,
        int $roleMask,
        string $userType,
        string $redirectUrl,
        ?UserEmailManagementService $emailService = null
    ) {
        $this->service      = $service;
        $this->roleMask     = $roleMask;
        $this->userType     = $userType;
        $this->redirectUrl  = $redirectUrl;
        $this->emailService = $emailService;
    }

    /**
     * Resolve UserEmailManagementService lazily if not explicitly injected.
     */
    private function getEmailService(): UserEmailManagementService
    {
        if ($this->emailService === null) {
            global $db;
            $repo   = new UserRepository($db);
            $mailer = new PhpMailerService();
            $this->emailService = new UserEmailManagementService($repo, $mailer);
        }
        return $this->emailService;
    }

    // ─── GET  /admin/students.php  or  GET  /admin/teachers.php ───
    // ─── POST /admin/students.php  or  POST /admin/teachers.php ───
    public function handle(): void
    {
        $action         = $_GET['action'] ?? 'list';
        $successMessage = $_SESSION['success_message'] ?? '';
        $errorMessage   = $_SESSION['error_message']   ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($action, $successMessage, $errorMessage);
        }

        // ─── GET: Listing ─────────────────────────────────────────
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $limit      = 10;

        $sort       = $_GET['sort'] ?? 'id';
        $order      = strtoupper($_GET['order'] ?? 'DESC');
        if (!in_array($sort, ['id', 'name', 'email', 'status'])) $sort = 'id';
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $paginated  = $this->service->getPaginatedUsersByRole($this->roleMask, $page, $limit, $search, $sort, $order);
        $users      = $paginated['data'];
        $totalPages = (int) $paginated['pages'];
        $userType   = $this->userType;

        $this->renderView($users, $totalPages, $successMessage, $errorMessage, $userType, $search, $page, $sort, $order);
    }

    // ─── Private: handle POST actions ─────────────────────────────
    private function handlePost(string $action, string &$successMessage, string &$errorMessage): void
    {
        try {
            validateCsrfToken($_POST['csrf_token'] ?? '');

            if ($action === 'create') {
                $this->service->createUser($_POST, $this->roleMask);
                $_SESSION['success_message'] = "{$this->userType} created successfully.";
                header("Location: {$this->redirectUrl}");
                exit;
            }

            if ($action === 'edit') {
                $id = (int) ($_POST['id'] ?? 0);
                $this->service->updateUser($id, $_POST);
                $_SESSION['success_message'] = "{$this->userType} updated successfully.";
                header("Location: {$this->redirectUrl}");
                exit;
            }

            if ($action === 'status') {
                $id     = (int) ($_POST['id'] ?? 0);
                $status = (int) ($_POST['status'] ?? 0);
                $this->service->updateStatus($id, $status);
                $_SESSION['success_message'] = 'Status updated.';
                header("Location: {$this->redirectUrl}");
                exit;
            }

            if ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                $this->service->deleteUser($id);
                $_SESSION['success_message'] = "{$this->userType} deleted.";
                header("Location: {$this->redirectUrl}");
                exit;
            }

            if ($action === 'change_email') {
                if (!isSuperAdmin() && !hasPermission('users.change_email')) {
                    throw new \Exception('You do not have permission to change user email.');
                }

                $id       = (int) ($_POST['id'] ?? 0);
                $newEmail = (string) ($_POST['new_email'] ?? '');

                global $auth;
                $adminId = ($auth && $auth->isLoggedIn()) ? (int) $auth->getUserId() : 1;
                $ip      = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                $ua      = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

                $this->getEmailService()->changeUserEmail(
                    $adminId,
                    $id,
                    $this->roleMask,
                    $newEmail,
                    $ip,
                    $ua
                );

                $_SESSION['success_message'] = "{$this->userType} email updated successfully.";
                header("Location: {$this->redirectUrl}");
                exit;
            }

        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            $errorMessage = $e->getMessage() ?: 'Email address already exists.';
        } catch (\Throwable $e) {
            $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // ─── Private: render view based on user type ──────────────────
    private function renderView(
        array  $users,
        int    $totalPages,
        string $successMessage,
        string $errorMessage,
        string $userType,
        string $search = '',
        int    $page = 1,
        string $sort = 'id',
        string $order = 'DESC'
    ): void {
        if ($this->userType === 'Student') {
            $students = $users;
            require __DIR__ . '/../../../views/admin/students.php';
        } else {
            $teachers = $users;
            require __DIR__ . '/../../../views/admin/teachers.php';
        }
    }
}
