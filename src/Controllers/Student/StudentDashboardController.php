<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Services\NoticeService;

/**
 * StudentDashboardController — Thin Controller
 * Fetches active notices for students via NoticeService.
 * No SQL in this controller — all DB access via NoticeService → NoticeRepository.
 */
class StudentDashboardController
{
    private NoticeService $noticeService;

    public function __construct(NoticeService $noticeService)
    {
        $this->noticeService = $noticeService;
    }

    public function index(): void
    {
        // Fetch active notices targeted at students (or both)
        $notices = $this->noticeService->getActiveNoticesForAudience('student', 10);

        require __DIR__ . '/../../../views/student/dashboard.php';
    }
}
