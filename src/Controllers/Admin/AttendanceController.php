<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\AttendanceService;
use App\Repositories\ClassroomRepository;

/**
 * AttendanceController (Admin / Super Admin)
 *
 * Thin controller: validates input, calls AttendanceService, renders view.
 * No business logic or SQL here.
 */
class AttendanceController
{
    private AttendanceService   $attendanceService;
    private ClassroomRepository $classroomRepo;

    public function __construct(
        AttendanceService   $attendanceService,
        ClassroomRepository $classroomRepo
    ) {
        $this->attendanceService = $attendanceService;
        $this->classroomRepo     = $classroomRepo;
    }

    /**
     * Overview page: one row per classroom with teacher + student info.
     * Replaces old session-level overview.
     */
    public function overview(): void
    {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $rows  = $this->attendanceService->getAdminClassroomList($limit, $offset);
        $total = $this->attendanceService->countAdminClassrooms();
        $pages = (int) ceil($total / $limit);

        $pageTitle  = 'Attendance Overview';
        $activeMenu = 'admin_attendance';

        require __DIR__ . '/../../../views/admin/attendance/overview.php';
    }

    /**
     * Detail page: full session-by-session attendance for one classroom.
     * Shows student and teacher attendance side by side.
     * URL: /admin/attendance/detail.php?classroom_id=X
     */
    public function detail(): void
    {
        $classroomId = (int) ($_GET['classroom_id'] ?? 0);

        // Validate classroom_id — must be a positive integer
        if ($classroomId <= 0) {
            http_response_code(404);
            exit('<h1>404 — Page not found.</h1>');
        }

        // Fetch classroom (includes teacher_id, student_id via ClassroomRepository::findById)
        $classroom = $this->classroomRepo->findById($classroomId);
        if (!$classroom) {
            http_response_code(404);
            exit('<h1>404 — Classroom not found.</h1>');
        }

        $studentId = (int) ($classroom['student_id'] ?? 0);
        $teacherId = (int) ($classroom['teacher_id'] ?? 0);

        // Pagination for the session list
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $totalSessions = $this->attendanceService->countAdminClassroomSessions($classroomId);
        $pages         = (int) ceil($totalSessions / $limit);

        // Summary stats (total, student present/absent, teacher present/absent)
        $summary = $this->attendanceService->getAdminClassroomSummary(
            $classroomId,
            $studentId,
            $teacherId
        );

        // Paginated session list with both student + teacher presence flags
        $sessions = $this->attendanceService->getAdminClassroomDetail(
            $classroomId,
            $studentId,
            $teacherId,
            $limit,
            $offset
        );

        $pageTitle  = 'Attendance — ' . htmlspecialchars($classroom['class_name'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'admin_attendance';

        require __DIR__ . '/../../../views/admin/attendance/detail.php';
    }
}
