<?php

namespace App\Controllers\Admin;

use App\Services\ClassroomService;
use PDO;

class AdminClassroomController
{
    private ClassroomService $service;

    public function __construct(ClassroomService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        global $auth;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $limit = 20;

        $result = $this->service->getPaginatedClassrooms($page, $limit, $search);
        
        $classrooms = $result['data'];
        $totalPages = $result['pages'];
        $currentPage = $result['current_page'];
        
        $pageTitle = 'Manage Classrooms';
        $activeMenu = 'classrooms_manage';

        require __DIR__ . '/../../../views/admin/classrooms/index.php';
    }

    public function create()
    {
        global $auth, $db;
        $pageTitle = 'Create Classroom';
        $activeMenu = 'classrooms_create';

        // Fetch Teachers (roles_mask & 4 = MANAGER) and Students (roles_mask & 1 = CONSUMER)
        $teachers = $this->getUsersByRole(ROLE_TEACHER, $db);
        $students = $this->getUsersByRole(ROLE_STUDENT, $db);

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->service->createClassroom($_POST, $auth->getUserId());
                $success = "Classroom created successfully.";
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        require __DIR__ . '/../../../views/admin/classrooms/create.php';
    }

    public function edit()
    {
        global $auth, $db;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $classroom = $this->service->getClassroomDetails($id);

        if (!$classroom) {
            die("Classroom not found.");
        }

        $pageTitle = 'Edit Classroom';
        $activeMenu = 'classrooms_manage';

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->service->updateClassroom($id, $_POST);
                $success = "Classroom updated successfully.";
                $classroom = $this->service->getClassroomDetails($id); // refresh data
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        require __DIR__ . '/../../../views/admin/classrooms/edit.php';
    }
    
    public function delete()
    {
        global $auth;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            $this->service->deleteClassroom($id);
        }
        
        header('Location: /admin/classrooms/index.php');
        exit;
    }

    private function getUsersByRole(int $roleMask, PDO $db): array
    {
        $stmt = $db->prepare("SELECT id, username, email FROM users WHERE (roles_mask & :role) > 0 AND status = 0");
        $stmt->execute(['role' => $roleMask]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
