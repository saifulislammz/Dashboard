<?php
/**
 * Public entry point: Upload Admin Voice Review (AJAX, POST only)
 * Accessible only by ROLE_ADMIN with quiz.review permission
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/QuizRepository.php';
require_once __DIR__ . '/../../../src/Services/QuizService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/QuizController.php';

requireRole(ROLE_ADMIN);
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';
requirePermission('quiz.review');

$repo       = new \App\Repositories\QuizRepository($db);
$service    = new \App\Services\QuizService($repo);
$controller = new \App\Controllers\Admin\QuizController($service, $repo);
$controller->uploadAdminVoice();

