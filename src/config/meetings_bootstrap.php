<?php

/**
 * Meeting Module Bootstrap Helper
 *
 * Configures the DI Container for the meeting module dependencies.
 * Include this file in any meeting module public entry point.
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
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use App\Utils\Container;

$container = new Container();

// Register the Database Connection
// Use a closure that captures $db from the global scope (set by database.php above)
$container->set(PDO::class, function() use (&$db) { return $db; });

// Register Repositories
$container->set(\App\Repositories\ClassroomRepository::class, fn($c) => new \App\Repositories\ClassroomRepository($c->get(PDO::class)));
$container->set(\App\Repositories\ClassSessionRepository::class, fn($c) => new \App\Repositories\ClassSessionRepository($c->get(PDO::class)));
$container->set(\App\Repositories\SessionMeetingRepository::class, fn($c) => new \App\Repositories\SessionMeetingRepository($c->get(PDO::class)));
$container->set(\App\Repositories\ProviderAccountRepository::class, fn($c) => new \App\Repositories\ProviderAccountRepository($c->get(PDO::class)));
$container->set(\App\Repositories\MeetingJobRepository::class, fn($c) => new \App\Repositories\MeetingJobRepository($c->get(PDO::class)));
$container->set(\App\Repositories\AttendanceRepository::class, fn($c) => new \App\Repositories\AttendanceRepository($c->get(PDO::class)));

// Register Services
$container->set(\App\Services\Meetings\MeetingProviderFactory::class, fn($c) => new \App\Services\Meetings\MeetingProviderFactory($c->get(PDO::class), $c->get(\App\Repositories\ProviderAccountRepository::class)));

$container->set(\App\Services\Meetings\MeetingService::class, fn($c) => new \App\Services\Meetings\MeetingService(
    $c->get(\App\Services\Meetings\MeetingProviderFactory::class),
    $c->get(\App\Repositories\ClassSessionRepository::class),
    $c->get(\App\Repositories\SessionMeetingRepository::class),
    $c->get(\App\Repositories\ProviderAccountRepository::class)
));

$container->set(\App\Services\Sessions\ClassSessionService::class, fn($c) => new \App\Services\Sessions\ClassSessionService(
    $c->get(\App\Repositories\ClassSessionRepository::class),
    $c->get(\App\Repositories\SessionMeetingRepository::class),
    $c->get(\App\Repositories\MeetingJobRepository::class),
    $c->get(\App\Services\Meetings\MeetingService::class)
));

$container->set(\App\Services\AttendanceService::class, fn($c) => new \App\Services\AttendanceService(
    $c->get(\App\Repositories\AttendanceRepository::class),
    $c->get(\App\Repositories\ClassSessionRepository::class),
    $c->get(PDO::class)
));
