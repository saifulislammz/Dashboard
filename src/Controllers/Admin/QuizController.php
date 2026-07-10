<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\QuizRepository;
use App\Services\QuizService;

/**
 * QuizController (Admin) — Thin Controller
 * শুধু request নেওয়া, Service call করা, View render করা।
 * কোনো business logic এখানে নেই।
 */
class QuizController
{
    private QuizService    $quizService;
    private QuizRepository $repo;

    public function __construct(QuizService $quizService, QuizRepository $repo)
    {
        $this->quizService = $quizService;
        $this->repo        = $repo;
    }

    // ──────────────────────────────────────────────
    // GET /admin/quiz/index.php
    // ──────────────────────────────────────────────
    public function index(): void
    {
        $filters  = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $data     = $this->quizService->getQuizList($filters, $page);
        $badge    = $this->quizService->getUnreviewedVoiceCount();

        require_once __DIR__ . '/../../../views/admin/quiz/index.php';
    }

    // ──────────────────────────────────────────────
    // GET  /admin/quiz/create.php
    // POST /admin/quiz/create.php
    // ──────────────────────────────────────────────
    public function create(): void
    {
        $error   = null;
        $success = null;
        $input   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCsrfToken($_POST['csrf_token'] ?? '');

            $input = $_POST;
            try {
                global $auth;
                $adminId = (int) $auth->getUserId();
                $quizId  = $this->quizService->createQuiz($input, $adminId);
                header('Location: /admin/quiz/view.php?id=' . $quizId . '&created=1');
                exit;
            } catch (\InvalidArgumentException $e) {
                $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            } catch (\Throwable $e) {
                error_log('[QuizController::create] ' . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }

        require_once __DIR__ . '/../../../views/admin/quiz/create.php';
    }

    // ──────────────────────────────────────────────
    // GET  /admin/quiz/edit.php?id=X
    // POST /admin/quiz/edit.php
    // ──────────────────────────────────────────────
    public function edit(): void
    {
        $quizId  = (int) ($_REQUEST['id'] ?? 0);
        $error   = null;
        $quiz    = null;

        try {
            $quiz = $this->quizService->getQuizForEdit($quizId);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            require_once __DIR__ . '/../../../views/admin/quiz/edit.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCsrfToken($_POST['csrf_token'] ?? '');

            try {
                $this->quizService->updateQuiz($quizId, $_POST);
                header('Location: /admin/quiz/view.php?id=' . $quizId . '&updated=1');
                exit;
            } catch (\InvalidArgumentException $e) {
                $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                // re-fetch fresh data for form
                $quiz  = $this->quizService->getQuizForEdit($quizId);
            } catch (\Throwable $e) {
                error_log('[QuizController::edit] ' . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }

        require_once __DIR__ . '/../../../views/admin/quiz/edit.php';
    }

    // ──────────────────────────────────────────────
    // POST /admin/quiz/delete.php
    // ──────────────────────────────────────────────
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        validateCsrfToken($_POST['csrf_token'] ?? '');
        $quizId = (int) ($_POST['id'] ?? 0);

        try {
            $this->quizService->deleteQuiz($quizId);
            header('Location: /admin/quiz/index.php?deleted=1');
        } catch (\Throwable $e) {
            error_log('[QuizController::delete] ' . $e->getMessage());
            header('Location: /admin/quiz/index.php?error=delete_failed');
        }
        exit;
    }

    // ──────────────────────────────────────────────
    // GET /admin/quiz/view.php?id=X
    // ──────────────────────────────────────────────
    public function view(): void
    {
        $quizId = (int) ($_GET['id'] ?? 0);
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $data = $this->quizService->getAdminReport($quizId, $page);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }

        // Report view open করলে সব unread badge clear
        $this->repo->markAllNotified($quizId);

        $created = isset($_GET['created']);
        $updated = isset($_GET['updated']);

        require_once __DIR__ . '/../../../views/admin/quiz/view.php';
    }

    // ──────────────────────────────────────────────
    // GET /admin/quiz/serve_voice.php?a=ATTEMPT_ID
    // Auth-protected ভয়েস ফাইল serve করা
    // ──────────────────────────────────────────────
    public function serveVoice(): void
    {
        $attemptId = (int) ($_GET['a'] ?? 0);
        if ($attemptId <= 0) {
            http_response_code(400);
            exit;
        }

        $attempt = $this->repo->findAttemptById($attemptId);

        if (!$attempt || empty($attempt['voice_file_path'])) {
            http_response_code(404);
            exit;
        }

        $filePath = __DIR__ . '/../../../storage/' . $attempt['voice_file_path'];
        $realPath = realpath($filePath);

        // Path traversal প্রতিরোধ
        $storageBase = realpath(__DIR__ . '/../../../storage/quiz_voices');
        if (!$realPath || !str_starts_with($realPath, $storageBase)) {
            http_response_code(403);
            exit;
        }

        if (!file_exists($realPath)) {
            http_response_code(404);
            exit;
        }

        $mime = mime_content_type($realPath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($realPath));
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, no-cache');
        readfile($realPath);
        exit;
    }

    // ──────────────────────────────────────────────
    // POST /admin/quiz/review_voice.php  (AJAX)
    // ──────────────────────────────────────────────
    public function reviewVoice(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        // JSON body নেওয়া (Alpine.js fetch করে JSON পাঠায়)
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $csrfToken = $body['csrf_token'] ?? '';
        validateCsrfToken($csrfToken);

        $attemptId = (int) ($body['attempt_id'] ?? 0);
        $note      = trim($body['note'] ?? '');

        if ($attemptId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid attempt.']);
            exit;
        }

        try {
            $this->quizService->saveVoiceReviewNote($attemptId, $note);
            echo json_encode(['success' => true, 'message' => 'Note saved.']);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log('[QuizController::reviewVoice] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save.']);
        }
        exit;
    }
}
