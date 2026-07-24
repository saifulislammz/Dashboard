<?php

declare(strict_types=1);

namespace App\Controllers\Quiz;

use App\Repositories\QuizRepository;
use App\Services\QuizService;

/**
 * QuizPlayerController — Public (No Login Required)
 * Handles all public routes from Guest form to quiz play.
 */
class QuizPlayerController
{
    private QuizService    $quizService;
    private QuizRepository $repo;

    public function __construct(QuizService $quizService, QuizRepository $repo)
    {
        $this->quizService = $quizService;
        $this->repo        = $repo;
    }

    // ──────────────────────────────────────────────
    // GET  /quiz/play.php?id=X    → Show guest form
    // POST /quiz/play.php         → Create attempt → redirect
    // GET  /quiz/play.php?t=TOKEN → Quiz player
    // ──────────────────────────────────────────────
    public function play(): void
    {
        // ─── Quiz player (with token)
        if (isset($_GET['t'])) {
            $this->showPlayer();
            return;
        }

        // ─── Guest form POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleGuestFormSubmit();
            return;
        }

        // ─── Guest form GET (with quiz id)
        $quizId = (int) ($_GET['id'] ?? 0);
        if ($quizId <= 0) {
            http_response_code(400);
            echo 'Quiz not found.';
            exit;
        }

        $quiz = $this->repo->findActiveQuizById($quizId);
        if (!$quiz) {
            http_response_code(404);
            echo 'The quiz was not found or is currently unavailable.';
            exit;
        }

        $error = null;
        require_once __DIR__ . '/../../../views/quiz/guest_form.php';
    }

    // ──────────────────────────────────────────────
    // POST /quiz/submit_answer.php  (AJAX JSON)
    // ──────────────────────────────────────────────
    public function submitAnswer(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // CSRF
        validateCsrfToken($body['csrf_token'] ?? '');

        $token            = $body['token'] ?? '';
        $questionId       = (int) ($body['question_id'] ?? 0);
        $selectedOptionId = (int) ($body['option_id'] ?? 0);

        if ($token === '' || $questionId <= 0 || $selectedOptionId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        // Verify attempt from Token (IDOR protection)
        $attempt = $this->repo->findAttemptByToken($token);
        if (!$attempt || $attempt['status'] === 'voice_submitted' || $attempt['status'] === 'completed') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        try {
            $result = $this->quizService->submitMCQAnswer(
                (int) $attempt['id'],
                $questionId,
                $selectedOptionId,
                (int) $attempt['quiz_id']
            );
            echo json_encode(['success' => true, ...$result]);
        } catch (\Throwable $e) {
            error_log('[QuizPlayerController::submitAnswer] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not save the answer.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────
    // POST /quiz/upload_voice.php  (multipart/form-data)
    // ──────────────────────────────────────────────
    public function uploadVoice(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        validateCsrfToken($_POST['csrf_token'] ?? '');

        $token = $_POST['token'] ?? '';
        $attempt = $this->repo->findAttemptByToken($token);

        if (!$attempt) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        if ($attempt['voice_submitted']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Voice has already been submitted.']);
            exit;
        }

        if (empty($_FILES['voice_file']) || $_FILES['voice_file']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No voice file found.']);
            exit;
        }

        // MCQ score finalize (before voice submit)
        try {
            $scoreData = $this->quizService->finalizeAttempt((int) $attempt['id']);
        } catch (\Throwable $e) {
            error_log('[QuizPlayerController::uploadVoice finalize] ' . $e->getMessage());
            $scoreData = ['correct_answers' => 0, 'total_questions' => 0, 'score_pct' => 0];
        }

        try {
            $this->quizService->uploadVoiceRecording((int) $attempt['id'], $_FILES['voice_file']);
            echo json_encode([
                'success'  => true,
                'message'  => 'Voice successfully submitted.',
                'redirect' => '/quiz/result.php?t=' . urlencode($token),
                'score'    => $scoreData,
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log('[QuizPlayerController::uploadVoice] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────
    // GET /quiz/result.php?t=TOKEN
    // ──────────────────────────────────────────────
    public function result(): void
    {
        $token = $_GET['t'] ?? '';
        if ($token === '') {
            http_response_code(400);
            echo 'Invalid request.';
            exit;
        }

        try {
            $data = $this->quizService->getResultData($token);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }

        $attempt   = $data['attempt'];
        $breakdown = $data['breakdown'];

        require_once __DIR__ . '/../../../views/quiz/result.php';
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    /**
     * Guest form POST → create attempt → player redirect
     */
    private function handleGuestFormSubmit(): void
    {
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $quizId = (int) ($_POST['quiz_id'] ?? 0);
        $quiz   = $this->repo->findActiveQuizById($quizId);

        if (!$quiz) {
            http_response_code(404);
            echo 'Quiz not found.';
            exit;
        }

        $error = null;

        try {
            $participant = $this->quizService->validateParticipant($_POST);
            $result      = $this->quizService->startOrResumeAttempt($quizId, $participant);
            header('Location: /quiz/play.php?t=' . urlencode($result['token']));
            exit;
        } catch (\RuntimeException $e) {
            // If permanently blocked → error message
            $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        } catch (\InvalidArgumentException $e) {
            $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        } catch (\Throwable $e) {
            error_log('[QuizPlayerController::handleGuestFormSubmit] ' . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }

        // If there is an error, show form again
        require_once __DIR__ . '/../../../views/quiz/guest_form.php';
    }

    /**
     * Show quiz player with Token
     */
    private function showPlayer(): void
    {
        $token   = $_GET['t'] ?? '';
        $attempt = $this->repo->findAttemptByToken($token);

        if (!$attempt) {
            http_response_code(403);
            echo 'Not authorized. Please use the correct link.';
            exit;
        }

        // Voice already submitted or quiz completed → redirect to result
        if ($attempt['voice_submitted'] || $attempt['status'] === 'completed') {
            header('Location: /quiz/result.php?t=' . urlencode($token));
            exit;
        }

        $quizId    = (int) $attempt['quiz_id'];
        $quiz      = $this->repo->findActiveQuizById($quizId);
        $questions = $this->quizService->getQuestionsForPlayer($quizId);

        if (!$quiz || empty($questions)) {
            http_response_code(404);
            echo 'Quiz not found or there are no questions.';
            exit;
        }

        require_once __DIR__ . '/../../../views/quiz/play.php';
    }
}
