<?php
/**
 * Public entry point: Quiz Guest Form + Player
 * No login required — public URL shared by admin
 *
 * GET  ?id=X     → Guest info form
 * POST           → Submit guest form → create attempt → redirect
 * GET  ?t=TOKEN  → Show quiz player
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

$controller->play();
