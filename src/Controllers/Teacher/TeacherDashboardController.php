<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Services\NoticeService;

/**
 * TeacherDashboardController — Thin Controller
 * Fetches active notices for teachers via NoticeService.
 * No SQL in this controller — all DB access via NoticeService → NoticeRepository.
 */
class TeacherDashboardController
{
    private NoticeService $noticeService;

    public function __construct(NoticeService $noticeService)
    {
        $this->noticeService = $noticeService;
    }

    public function index(): void
    {
        // Fetch active notices targeted at teachers (or both)
        $notices = $this->noticeService->getActiveNoticesForAudience('teacher', 10);

        require __DIR__ . '/../../../views/teacher/dashboard.php';
    }
}
