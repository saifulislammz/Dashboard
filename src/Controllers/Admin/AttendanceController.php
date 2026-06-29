<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\AttendanceService;
use App\Repositories\ClassroomRepository;
use App\Repositories\ClassSessionRepository;

/**
 * AttendanceController (Admin)
 *
 * Thin controller: validates input, calls AttendanceService, renders view.
 * No business logic or SQL here.
 */
class AttendanceController
{
    private AttendanceService      $attendanceService;
    private ClassroomRepository    $classroomRepo;
    private ClassSessionRepository $sessionRepo;

    public function __construct(
        AttendanceService      $attendanceService,
        ClassroomRepository    $classroomRepo,
        ClassSessionRepository $sessionRepo
    ) {
        $this->attendanceService = $attendanceService;
        $this->classroomRepo     = $classroomRepo;
        $this->sessionRepo       = $sessionRepo;
    }

    /**
     * Overview page: all sessions with attendance stats, filterable.
     */
    public function overview(): void
    {
        $page        = max(1, (int) ($_GET['page'] ?? 1));
        $limit       = 20;
        $offset      = ($page - 1) * $limit;
        $classroomId = (int) ($_GET['classroom_id'] ?? 0);
        $dateFrom    = trim($_GET['date_from'] ?? '');
        $dateTo      = trim($_GET['date_to'] ?? '');

        // Basic date validation
        if (!empty($dateFrom) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if (!empty($dateTo) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }

        $rows  = $this->attendanceService->getAdminOverview($limit, $offset, $classroomId, $dateFrom, $dateTo);
        $total = $this->attendanceService->countAdminOverview($classroomId, $dateFrom, $dateTo);
        $pages = (int) ceil($total / $limit);

        // For classroom filter dropdown
        $classrooms = $this->classroomRepo->getPaginatedClassrooms(200, 0);

        $pageTitle  = 'Attendance Overview';
        $activeMenu = 'admin_attendance';

        require __DIR__ . '/../../../views/admin/attendance/overview.php';
    }

    /**
     * Detailed report for a single session.
     */
    public function sessionReport(): void
    {
        $sessionId = (int) ($_GET['session_id'] ?? 0);
        if ($sessionId <= 0) {
            http_response_code(400);
            die('<h1>Invalid session ID.</h1>');
        }

        $report = $this->attendanceService->getSessionReport($sessionId);
        if (empty($report)) {
            http_response_code(404);
            die('<h1>Session not found.</h1>');
        }

        $pageTitle  = 'Session Attendance Report';
        $activeMenu = 'admin_attendance';

        require __DIR__ . '/../../../views/admin/attendance/session_report.php';
    }
}
