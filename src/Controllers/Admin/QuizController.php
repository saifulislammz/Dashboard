<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\QuizRepository;
use App\Services\QuizService;

/**
 * QuizController (Admin) — Thin Controller
 * Only take requests, call Service, and render View.
 * No business logic here.
 */
class QuizController
{
    private QuizService     $quizService;
    private ?QuizRepository $repo;

    public function __construct(QuizService $quizService, ?QuizRepository $repo = null)
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
            'type'   => $_GET['type'] ?? '',
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

        // Clear all unread badges when Report view is opened (via Service Layer)
        $this->quizService->markAllNotified($quizId);

        $created = isset($_GET['created']);
        $updated = isset($_GET['updated']);

        require_once __DIR__ . '/../../../views/admin/quiz/view.php';
    }

    // ──────────────────────────────────────────────
    // GET /admin/quiz/serve_voice.php?a=ATTEMPT_ID
    // Serve Auth-protected student voice file
    // ──────────────────────────────────────────────
    public function serveVoice(): void
    {
        $attemptId = (int) ($_GET['a'] ?? 0);
        if ($attemptId <= 0) {
            http_response_code(400);
            exit;
        }

        $attempt = $this->quizService->getAttemptById($attemptId);

        if (!$attempt || empty($attempt['voice_file_path'])) {
            http_response_code(404);
            exit;
        }

        $filePath = __DIR__ . '/../../../storage/' . $attempt['voice_file_path'];
        $realPath = realpath($filePath);

        // Prevent path traversal
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
    // GET /admin/quiz/serve_admin_voice.php?a=ATTEMPT_ID
    // Serve Auth-protected admin review voice file
    // ──────────────────────────────────────────────
    public function serveAdminVoice(): void
    {
        $attemptId = (int) ($_GET['a'] ?? 0);
        if ($attemptId <= 0) {
            http_response_code(400);
            exit;
        }

        $attempt = $this->quizService->getAttemptById($attemptId);

        if (!$attempt || empty($attempt['admin_voice_file_path'])) {
            http_response_code(404);
            exit;
        }

        $filePath = __DIR__ . '/../../../storage/' . $attempt['admin_voice_file_path'];
        $realPath = realpath($filePath);

        // Prevent path traversal
        $storageBase = realpath(__DIR__ . '/../../../storage/admin_review_voices');
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
    // POST /admin/quiz/upload_admin_voice.php (AJAX multipart)
    // ──────────────────────────────────────────────
    public function uploadAdminVoice(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        validateCsrfToken($_POST['csrf_token'] ?? '');

        $attemptId = (int) ($_POST['attempt_id'] ?? 0);
        if ($attemptId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid attempt.']);
            exit;
        }

        if (empty($_FILES['voice_file']) || $_FILES['voice_file']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No audio file provided.']);
            exit;
        }

        $voiceMarks = isset($_POST['voice_marks']) && $_POST['voice_marks'] !== ''
            ? (float) $_POST['voice_marks']
            : null;
        $voiceNote = isset($_POST['voice_note'])
            ? trim($_POST['voice_note'])
            : null;

        try {
            $relativePath = $this->quizService->uploadAdminVoiceReview(
                $attemptId,
                $_FILES['voice_file'],
                $voiceMarks,
                $voiceNote
            );

            $waUrl = $this->quizService->generateWhatsAppShareUrl($attemptId);

            echo json_encode([
                'success'      => true,
                'message'      => 'Teacher voice review saved successfully.',
                'file_path'    => $relativePath,
                'whatsapp_url' => $waUrl,
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log('[QuizController::uploadAdminVoice] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save voice file.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────
    // GET /admin/quiz/get_whatsapp_url.php?a=ATTEMPT_ID (AJAX)
    // ──────────────────────────────────────────────
    public function getWhatsAppUrl(): void
    {
        header('Content-Type: application/json');

        $attemptId = (int) ($_GET['a'] ?? 0);
        if ($attemptId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid attempt ID']);
            exit;
        }

        try {
            $url = $this->quizService->generateWhatsAppShareUrl($attemptId);
            echo json_encode(['success' => true, 'whatsapp_url' => $url]);
        } catch (\Throwable $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
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

        // Take JSON body (Alpine.js sends JSON via fetch)
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $csrfToken = $body['csrf_token'] ?? '';
        validateCsrfToken($csrfToken);

        $attemptId  = (int) ($body['attempt_id'] ?? 0);
        $isReviewed = (bool) ($body['is_reviewed'] ?? false);

        if ($attemptId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid attempt.']);
            exit;
        }

        try {
            $this->quizService->toggleVoiceReview($attemptId, $isReviewed);
            echo json_encode(['success' => true, 'message' => 'Review status saved.']);
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
