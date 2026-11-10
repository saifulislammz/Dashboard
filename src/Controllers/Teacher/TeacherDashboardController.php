<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Repositories\ClassroomRepository;
use App\Repositories\ClassSessionRepository;
use App\Services\ClassroomService;
use App\Services\NoticeService;

/**
 * TeacherDashboardController — Thin Controller
 * Fetches active notices, assigned classrooms, and upcoming live sessions for teachers.
 */
class TeacherDashboardController
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

        // Fetch active notices targeted at this teacher (broadcast, teacher role, assigned classrooms, or specific user)
        $notices = $this->noticeService->getNoticesForUser($userId, 'teacher', null, 10);

        $classrooms = [];
        $upcomingSessions = [];

        if ($userId > 0) {
            $classroomService = $this->classroomService ?? (isset($db) ? new ClassroomService(new ClassroomRepository($db), null, null, $db) : null);
            if ($classroomService) {
                $classroomsResult = $classroomService->getTeacherClassrooms($userId, 1, 6);
                $classrooms = $classroomsResult['data'] ?? [];
            }

            $sessionRepo = $this->sessionRepo ?? (isset($db) ? new ClassSessionRepository($db) : null);
            if ($sessionRepo) {
                $upcomingSessions = $sessionRepo->getUpcomingForTeacher($userId, 5);
            }
        }

        require __DIR__ . '/../../../views/teacher/dashboard.php';
    }
}
