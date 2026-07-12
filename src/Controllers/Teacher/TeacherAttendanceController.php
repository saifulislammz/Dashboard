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

        $sessions = $this->attendanceService->getTeacherClassroomReport($classroomId, $limit, $offset);
        $total    = $this->attendanceService->countTeacherClassroomSessions($classroomId);
        $pages    = (int) ceil($total / $limit);

        $pageTitle  = 'Attendance — ' . htmlspecialchars($classroom['class_name'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'teacher_attendance';

        require __DIR__ . '/../../../views/teacher/attendance.php';
    }

    /**
     * Detailed session report for a specific session (teacher view).
     */
    public function sessionReport(): void
    {
        global $auth;
        $teacherId = $auth->getUserId();
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

        // IDOR: verify teacher owns the classroom of this session
        $classroomId = (int) ($report['session']['classroom_id'] ?? 0);
        $classroom   = $this->classroomRepo->findById($classroomId);
        if (!$classroom || (int) $classroom['teacher_id'] !== $teacherId) {
            http_response_code(403);
            die('<h1>Access denied.</h1>');
        }

        $pageTitle  = 'Session Report — ' . htmlspecialchars($report['session']['topic'] ?? 'Session', ENT_QUOTES, 'UTF-8');
        $activeMenu = 'teacher_attendance';

        require __DIR__ . '/../../../views/teacher/attendance_session_report.php';
    }
}
