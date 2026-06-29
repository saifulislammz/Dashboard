<?php

/**
 * Meeting Module Bootstrap Helper
 *
 * Instantiates all meeting module dependencies.
 * Include this file in any meeting module public entry point.
 *
 * Usage:
 *   require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/roles.php';

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CsrfMiddleware.php';

// Aggressive autoloader for App\ namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = __DIR__ . '/../' . str_replace('\\', '/', $relative_class) . '.php';
        require_once $file;
    }
});

use App\Repositories\ClassroomRepository;
use App\Repositories\ClassSessionRepository;
use App\Repositories\SessionMeetingRepository;
use App\Repositories\ProviderAccountRepository;
use App\Repositories\MeetingJobRepository;
use App\Repositories\AttendanceRepository;
use App\Services\Meetings\MeetingProviderFactory;
use App\Services\Meetings\MeetingService;
use App\Services\Sessions\ClassSessionService;
use App\Services\AttendanceService;

// Instantiate repositories
$classroomRepo    = new ClassroomRepository($db);
$sessionRepo      = new ClassSessionRepository($db);
$meetingRepo      = new SessionMeetingRepository($db);
$providerRepo     = new ProviderAccountRepository($db);
$jobRepo          = new MeetingJobRepository($db);

// Instantiate services
$providerFactory   = new MeetingProviderFactory($db, $providerRepo);
$meetingService    = new MeetingService($providerFactory, $sessionRepo, $meetingRepo, $providerRepo);
$sessionService    = new ClassSessionService($sessionRepo, $meetingRepo, $jobRepo, $meetingService);
$attendanceRepo    = new AttendanceRepository($db);
$attendanceService = new AttendanceService($attendanceRepo, $sessionRepo, $db);
