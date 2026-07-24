<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Services\AttendanceService;
use App\Repositories\ClassroomRepository;

/**
 * TeacherAttendanceController
 *
 * Thin controller: validates teacher ownership, calls AttendanceService, renders view.
 */
class TeacherAttendanceController
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
     * Classroom attendance report: session-by-session summary.
     */
    public function classroomReport(): void
    {
        global $auth;
        $teacherId   = $auth->getUserId();
        $classroomId = (int) ($_GET['classroom_id'] ?? 0);

        // If no classroom_id supplied, show the teacher's classroom list so they can pick one
        if ($classroomId <= 0) {
            $classrooms = $this->classroomRepo->getTeacherClassrooms($teacherId, 50, 0);
            $pageTitle  = 'Attendance — Choose Classroom';
            $activeMenu = 'teacher_attendance';
            require __DIR__ . '/../../../views/teacher/attendance_pick_classroom.php';
            return;
        }

        // IDOR protection: verify teacher owns this classroom
        $classroom = $this->classroomRepo->findById($classroomId);
        if (!$classroom || (int) $classroom['teacher_id'] !== $teacherId) {
            http_response_code(403);
            die('<h1>Access denied.</h1>');
        }

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        // getTeacherSummary returns total_all_sessions (all non-cancelled, for pagination)
        // alongside past-session stats — one query instead of two.
        $summary  = $this->attendanceService->getTeacherSummary($classroomId);
        $sessions = $this->attendanceService->getTeacherClassroomReport($classroomId, $limit, $offset);
        $pages    = (int) ceil(($summary['total_all_sessions'] ?? 0) / $limit);

        $pageTitle  = 'Attendance — ' . htmlspecialchars($classroom['class_name'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'teacher_attendance';

        require __DIR__ . '/../../../views/teacher/attendance.php';
    }
}
