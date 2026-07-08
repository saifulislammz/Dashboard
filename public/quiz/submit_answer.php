<?php
/**
 * Public entry point: Submit MCQ Answer (AJAX JSON)
 * No login required — token-based auth
 */
declare(strict_types=1);

require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/QuizRepository.php';
require_once __DIR__ . '/../../src/Services/QuizService.php';
require_once __DIR__ . '/../../src/Controllers/Quiz/QuizPlayerController.php';

$repo       = new \App\Repositories\QuizRepository($db);
$service    = new \App\Services\QuizService($repo);
$controller = new \App\Controllers\Quiz\QuizPlayerController($service, $repo);

$controller->submitAnswer();
