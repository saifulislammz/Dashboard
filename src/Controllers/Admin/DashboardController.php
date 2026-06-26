<?php

namespace App\Controllers\Admin;

use App\Services\AnalyticsService;

class DashboardController
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        global $auth, $db;
        
        $stats = $this->analyticsService->getDashboardStats();
        
        $pageTitle = 'Admin Dashboard';
        $activeMenu = 'dashboard';
        
        // Render view
        require __DIR__ . '/../../../views/admin/dashboard.php';
    }
}
