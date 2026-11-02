<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\AnalyticsService;
use App\Services\ClassObserverService;

class DashboardController
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(): void
    {
        global $auth, $db;
        
        $stats = $this->analyticsService->getDashboardStats();
        
        $userId = (int) ($auth ? $auth->getUserId() : 0);
        $observerService = new ClassObserverService($db);
        $observerData = $observerService->getObserverDashboardData($userId);

        $pageTitle = 'Admin Dashboard';
        $activeMenu = 'dashboard';
        
        // Render view
        require __DIR__ . '/../../../views/admin/dashboard.php';
    }
}
