<?php

namespace App\Controllers\Student;

use App\Services\ClassroomService;

class StudentClassroomController
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
        $limit = 20;

        $result = $this->service->getStudentClassrooms($auth->getUserId(), $page, $limit);
        
        $classrooms = $result['data'];
        $totalPages = $result['pages'];
        $currentPage = $result['current_page'];
        
        $pageTitle = 'My Classes';
        $activeMenu = 'classes';

        require __DIR__ . '/../../../views/student/classrooms.php';
    }

    public function show()
    {
        global $auth;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $classroom = $this->service->getClassroomDetails($id);

        // Security check: student can only view their own classrooms (single or group)
        if (!$classroom || !$this->service->isStudentInClassroom($id, (int) $auth->getUserId())) {
            http_response_code(403);
            die("Classroom not found or unauthorized.");
        }

        $pageTitle = 'Classroom Details';
        $activeMenu = 'classes';

        $resourceRepo = new \App\Repositories\ClassroomResourceRepository($GLOBALS['db']);
        $resources = $resourceRepo->getResourcesForClassroom($id, 'students');

        require __DIR__ . '/../../../views/student/classroom_details.php';
    }
}
