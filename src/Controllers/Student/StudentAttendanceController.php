<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Services\AttendanceService;
use App\Repositories\ClassroomRepository;

/**
 * StudentAttendanceController
 *
 * Thin controller: validates student ownership, calls AttendanceService, renders view.
 * Strict IDOR protection — students can only see their own data.
 */
class StudentAttendanceController
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
     * Student's personal attendance report for a classroom.
     * Shows overall percentage + session-by-session list.
     */
    public function myReport(): void
    {
        global $auth;
        $studentId   = $auth->getUserId();
        $classroomId = (int) ($_GET['classroom_id'] ?? 0);

        if ($classroomId <= 0) {
            // Show student's classroom list to pick from
            $classrooms = $this->classroomRepo->getStudentClassrooms($studentId, 50, 0);
            $pageTitle  = 'My Attendance — Choose Classroom';
            $activeMenu = 'student_attendance';
            require __DIR__ . '/../../../views/student/attendance_pick_classroom.php';
            return;
        }

        // IDOR protection: verify student is enrolled in this classroom
        $classroom = $this->classroomRepo->findById($classroomId);
        if (!$classroom || (int) $classroom['student_id'] !== $studentId) {
            http_response_code(403);
            die('<h1>Access denied.</h1>');
        }

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $summary  = $this->attendanceService->getStudentSummary($studentId, $classroomId);
        $sessions = $this->attendanceService->getStudentSessionList($studentId, $classroomId, $limit, $offset);
        $total    = $this->attendanceService->countStudentSessions($studentId, $classroomId);
        $pages    = (int) ceil($total / $limit);

        $pageTitle  = 'My Attendance — ' . htmlspecialchars($classroom['class_name'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'student_attendance';

        require __DIR__ . '/../../../views/student/attendance.php';
    }
}
