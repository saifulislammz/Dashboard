<?php

namespace App\Services;

use App\Repositories\AnalyticsRepository;

class AnalyticsService
{
    private AnalyticsRepository $analyticsRepository;

    public function __construct(AnalyticsRepository $analyticsRepository)
    {
        $this->analyticsRepository = $analyticsRepository;
    }

    public function getDashboardStats(): array
    {
        $counts = $this->analyticsRepository->getDashboardCounts();

        return [
            'total_students'    => $counts['total_students'],
            'total_teachers'    => $counts['total_teachers'],
            'total_notices'     => $counts['total_notices'],
            'total_classrooms'  => $counts['total_classrooms'],
            'total_quizzes'     => $counts['total_quizzes'] ?? 0,
            'recent_classrooms' => $this->analyticsRepository->getRecentClassrooms(20),
        ];
    }
}
