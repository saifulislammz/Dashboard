<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Repositories\ClassroomRepository;
use App\Repositories\ClassSessionRepository;
use App\Services\ClassroomService;
use App\Services\NoticeService;

/**
 * StudentDashboardController — Thin Controller
 * Fetches active notices, enrolled group classrooms, and upcoming live classes for students.
 */
class StudentDashboardController
{
    private NoticeService $noticeService;
    private ?ClassroomService $classroomService;
    private ?ClassSessionRepository $sessionRepo;

    public function __construct(
        NoticeService $noticeService,
        ?ClassroomService $classroomService = null,
        ?ClassSessionRepository $sessionRepo = null
    ) {
        $this->noticeService    = $noticeService;
        $this->classroomService = $classroomService;
        $this->sessionRepo      = $sessionRepo;
    }

    public function index(): void
    {
        global $auth, $db;
        $userId = $auth ? (int) $auth->getUserId() : 0;

        // Fetch active notices targeted at this student (broadcast, student role, enrolled classrooms, or specific user)
        $notices = $this->noticeService->getNoticesForUser($userId, 'student', null, 10);

        $classrooms = [];
        $upcomingSessions = [];

        if ($userId > 0) {
            $classroomService = $this->classroomService ?? (isset($db) ? new ClassroomService(new ClassroomRepository($db), null, null, $db) : null);
            if ($classroomService) {
                $classroomsResult = $classroomService->getStudentClassrooms($userId, 1, 6);
                $classrooms = $classroomsResult['data'] ?? [];
            }

            $sessionRepo = $this->sessionRepo ?? (isset($db) ? new ClassSessionRepository($db) : null);
            if ($sessionRepo) {
                $upcomingSessions = $sessionRepo->getUpcomingForStudent($userId, 5);
            }
        }

        require __DIR__ . '/../../../views/student/dashboard.php';
    }
}
