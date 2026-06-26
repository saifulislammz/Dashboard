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
        return [
            'total_students' => $this->analyticsRepository->getStudentCount(),
            'total_teachers' => $this->analyticsRepository->getTeacherCount(),
            'total_notices'  => $this->analyticsRepository->getNoticeCount(),
            'total_classrooms' => $this->analyticsRepository->getClassroomCount(),
            'recent_classrooms' => $this->analyticsRepository->getRecentClassrooms(20),
        ];
    }
}
