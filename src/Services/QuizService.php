<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QuizRepository;

/**
 * QuizService — Arabic Quiz System Business Logic Layer
 *
 * Controller only takes input and sends output.
 * All validation, business rules, and file handling happen here.
 */
class QuizService
{
    // Allowed voice MIME types
    private const ALLOWED_AUDIO_MIMES = [
        'audio/webm',
        'audio/ogg',
        'audio/wav',
        'audio/mp4',
        'audio/mpeg',
        'audio/x-m4a',
    ];

    // Maximum voice file size (10MB)
    private const MAX_VOICE_BYTES = 10 * 1024 * 1024;

    // Voice storage base path
    private const VOICE_STORAGE_BASE = __DIR__ . '/../../storage/quiz_voices';

    // Question types — only these three are valid
    private const VALID_QUESTION_TYPES = ['letter', 'pronunciation', 'voice'];

    public function __construct(private QuizRepository $repo) {}

    // ================================================================
    // ADMIN: QUIZ MANAGEMENT
    // ================================================================

    /**
     * Create new quiz (validation + DB save, in transaction)
     *
     * @throws \InvalidArgumentException on validation failure
     */
    public function createQuiz(array $input, int $adminId): int
    {
        $validated = $this->validateQuizInput($input);

        // Transaction: quiz + questions + options together
        $this->repo->beginTransaction();
        try {
            $quizId = $this->repo->createQuiz([
                'title'      => $validated['title'],
                'description' => $validated['description'],
                'status'     => $validated['status'],
                'created_by' => $adminId,
            ]);

            $this->saveQuestionsWithOptions($quizId, $validated['questions']);

            $this->repo->commit();
            return $quizId;
        } catch (\Throwable $e) {
            $this->repo->rollback();
            throw $e;
        }
    }

    /**
     * Update quiz (replaces all questions)
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException if quiz is not found
     */
    public function updateQuiz(int $quizId, array $input): void
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('Quiz not found.');
        }

        $validated = $this->validateQuizInput($input);

        $this->repo->beginTransaction();
        try {
            $this->repo->updateQuiz($quizId, [
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'status'      => $validated['status'],
            ]);

            // Delete old questions and save new ones
            $this->repo->deleteQuestionsByQuizId($quizId);
            $this->saveQuestionsWithOptions($quizId, $validated['questions']);

            $this->repo->commit();
        } catch (\Throwable $e) {
            $this->repo->rollback();
            throw $e;
        }
    }

    /**
     * Soft delete quiz
     *
     * @throws \RuntimeException
     */
    public function deleteQuiz(int $quizId): void
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('Quiz not found.');
        }
        $this->repo->softDeleteQuiz($quizId);
    }

    /**
     * Quiz list for Admin (paginated)
     */
    public function getQuizList(array $filters, int $page, int $perPage = 15): array
    {
        $quizzes = $this->repo->findAllQuizzes($filters, $page, $perPage);
        $total   = $this->repo->countAllQuizzes($filters);

        return [
            'quizzes'      => $quizzes,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * For quiz edit form — including questions + options
     */
    public function getQuizForEdit(int $quizId): array
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('Quiz not found.');
        }

        $questions = $this->repo->getQuestionsByQuizId($quizId);
        foreach ($questions as &$q) {
            $q['options'] = $q['type'] !== 'voice'
                ? $this->repo->getOptionsForQuestion((int) $q['id'])
                : [];
        }
        unset($q);

        $quiz['questions'] = $questions;
        return $quiz;
    }

    // ================================================================
    // ADMIN: REPORT & STATS
    // ================================================================

    /**
     * All data for Admin report page
     */
    public function getAdminReport(int $quizId, int $page, int $perPage = 20): array
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('Quiz not found.');
        }

        $stats    = $this->repo->getDashboardStats($quizId);
        $attempts = $this->repo->getAttemptList($quizId, $page, $perPage);
        $total    = $this->repo->countAttempts($quizId);

        // Add score percentage to each attempt
        foreach ($attempts as &$a) {
            $a['score_pct'] = $a['total_questions'] > 0
                ? round(($a['correct_answers'] / $a['total_questions']) * 100, 1)
                : 0;
        }
        unset($a);

        return [
            'quiz'         => $quiz,
            'stats'        => $stats,
            'attempts'     => $attempts,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Save voice review note
     */
    public function saveVoiceReviewNote(int $attemptId, string $note): void
    {
        $note = trim($note);
        if (mb_strlen($note) > 2000) {
            throw new \InvalidArgumentException('Note can be at most 2000 characters.');
        }
        $this->repo->saveVoiceReviewNote($attemptId, $note);
    }

    /**
     * Sidebar badge count
     */
    public function getUnreviewedVoiceCount(): int
    {
        return $this->repo->getGlobalUnreviewedVoiceCount();
    }

    // ================================================================
    // PUBLIC: GUEST FORM + QUIZ START
    // ================================================================

    /**
     * Guest form validation
     *
     * @return array validated & sanitized data
     * @throws \InvalidArgumentException
     */
    public function validateParticipant(array $input): array
    {
        $errors = [];

        // Name
        $name = trim($input['participant_name'] ?? '');
        if ($name === '') {
            $errors[] = 'Name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'Name can be at most 150 characters.';
        }

        // Gender — whitelist
        $gender = trim($input['gender'] ?? '');
        if (!in_array($gender, ['male', 'female'], true)) {
            $errors[] = 'Please select a valid gender.';
        }

        // WhatsApp Number
        $whatsapp = trim($input['whatsapp_number'] ?? '');
        if ($whatsapp === '') {
            $errors[] = 'WhatsApp Number is required.';
        } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $whatsapp)) {
            $errors[] = 'Please provide a valid WhatsApp Number (10-15 digits).';
        }

        // Email — optional
        $email = trim($input['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid Email address.';
        }
        if ($email !== '' && mb_strlen($email) > 150) {
            $errors[] = 'Email can be at most 150 characters.';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return [
            'participant_name' => $name,
            'gender'           => $gender,
            'whatsapp_number'  => $whatsapp,
            'email'            => $email !== '' ? $email : null,
        ];
    }

    /**
     * Start Quiz:
     * - Blocked forever? → error
     * - Existing in_progress? → reset and start with new token
     * - New participant → create new attempt
     *
     * @return array ['token' => string, 'attempt_id' => int]
     * @throws \RuntimeException
     */
    public function startOrResumeAttempt(int $quizId, array $participant): array
    {
        // Check permanently blocked
        if ($this->repo->isPhoneBlockedForQuiz($quizId, $participant['whatsapp_number'])) {
            throw new \RuntimeException(
                'This WhatsApp Number has already completed the quiz. Participation is no longer possible.'
            );
        }

        // Number of questions
        $questions   = $this->repo->getQuestionsByQuizId($quizId);
        $totalCount  = count($questions);
        $newToken    = bin2hex(random_bytes(32));

        // Check previous in_progress attempt
        $existing = $this->repo->findInProgressAttempt($quizId, $participant['whatsapp_number']);

        if ($existing) {
            // Reset — start completely fresh with a new token
            $this->repo->deleteAnswersForAttempt((int) $existing['id']);
            $this->repo->resetAttempt((int) $existing['id'], $newToken, $totalCount);
            $attemptId = (int) $existing['id'];
        } else {
            // Create new attempt
            $attemptId = $this->repo->createAttempt([
                'quiz_id'          => $quizId,
                'participant_name' => $participant['participant_name'],
                'gender'           => $participant['gender'],
                'whatsapp_number'  => $participant['whatsapp_number'],
                'email'            => $participant['email'],
                'total_questions'  => $totalCount,
                'session_token'    => $newToken,
            ]);
        }

        return [
            'token'      => $newToken,
            'attempt_id' => $attemptId,
        ];
    }

    // ================================================================
    // PUBLIC: QUIZ PLAY
    // ================================================================

    /**
     * Load questions for Quiz player
     * Order: letter → pronunciation → voice
     * Options: shuffle on every request
     */
    public function getQuestionsForPlayer(int $quizId): array
    {
        $questions = $this->repo->getQuestionsByQuizId($quizId);

        // Ensure order based on type
        $typeOrder = ['letter' => 0, 'pronunciation' => 1, 'voice' => 2];
        usort($questions, fn($a, $b) =>
            ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99)
        );

        foreach ($questions as &$q) {
            if ($q['type'] !== 'voice') {
                $options = $this->repo->getOptionsForQuestion((int) $q['id']);
                shuffle($options); // Random order each time
                $q['options'] = $options;
            } else {
                $q['options'] = [];
            }
        }
        unset($q);

        return $questions;
    }

    /**
     * Verify and save MCQ answer
     *
     * @return array ['correct' => bool, 'correct_option_id' => int]
     * @throws \RuntimeException if attempt or question is invalid
     */
    public function submitMCQAnswer(
        int $attemptId,
        int $questionId,
        int $selectedOptionId,
        int $quizId
    ): array {
        // Get correct answer from DB (client will not be trusted)
        $correctOption = $this->repo->getCorrectOptionForQuestion($questionId);
        if (!$correctOption) {
            throw new \RuntimeException('Question not found.');
        }

        $isCorrect = ((int) $correctOption['id'] === $selectedOptionId);

        $this->repo->recordAnswer($attemptId, $questionId, $selectedOptionId, $isCorrect);

        return [
            'correct'           => $isCorrect,
            'correct_option_id' => (int) $correctOption['id'],
        ];
    }

    /**
     * MCQ finished — calculate score and finalize attempt
     */
    public function finalizeAttempt(int $attemptId): array
    {
        // Count correct answers from DB
        $attempt = $this->repo->findAttemptById($attemptId);
        if (!$attempt) {
            throw new \RuntimeException('Attempt not found.');
        }

        $stats = $this->repo->getAnswerStats($attemptId);
        $correct = (int) ($stats['correct_count'] ?? 0);
        $total   = (int) ($attempt['total_questions'] ?? 0);

        $this->repo->finalizeAttempt($attemptId, $correct, $total);

        return [
            'correct_answers' => $correct,
            'total_questions' => $total,
            'score_pct'       => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Upload, verify, and save voice recording
     *
     * @throws \InvalidArgumentException if file is invalid
     * @throws \RuntimeException on save failure
     */
    public function uploadVoiceRecording(int $attemptId, array $file): string
    {
        // File upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('File upload failed. Please try again.');
        }

        // Size check
        if ($file['size'] > self::MAX_VOICE_BYTES) {
            throw new \InvalidArgumentException('Voice file can be at most 10 MB.');
        }

        // Validate MIME (with finfo — client header is not trusted)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_AUDIO_MIMES, true)) {
            throw new \InvalidArgumentException('Not an allowed audio format.');
        }

        // Secure random filename
        $ext      = $this->getExtensionFromMime($mimeType);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        // Create month-based directory
        $dirPath = self::VOICE_STORAGE_BASE . '/' . date('Y/m');
        if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
            throw new \RuntimeException('Could not create storage directory.');
        }

        $fullPath = $dirPath . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Could not save file.');
        }

        // Save relative path in DB (from storage root)
        $relativePath = 'quiz_voices/' . date('Y/m') . '/' . $filename;
        $saved = $this->repo->saveVoiceSubmission($attemptId, $relativePath);

        if (!$saved) {
            // If DB save fails, delete the file
            @unlink($fullPath);
            throw new \RuntimeException('Could not save data.');
        }

        return $relativePath;
    }

    /**
     * attempt + per-type breakdown for result page
     */
    public function getResultData(string $token): array
    {
        $attempt = $this->repo->findAttemptByToken($token);
        if (!$attempt) {
            throw new \RuntimeException('Result not found.');
        }

        // Score breakdown by type
        $breakdown = $this->repo->getAnswerBreakdown((int) $attempt['id']);

        return [
            'attempt'   => $attempt,
            'breakdown' => $breakdown,
        ];
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    /**
     * Quiz input validation (used in create & update)
     */
    private function validateQuizInput(array $input): array
    {
        $errors = [];

        $title = trim($input['title'] ?? '');
        if ($title === '') {
            $errors[] = 'Title is required.';
        } elseif (mb_strlen($title) > 255) {
            $errors[] = 'Title can be at most 255 characters.';
        }

        $status = $input['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $description = trim($input['description'] ?? '');
        if (mb_strlen($description) > 5000) {
            $errors[] = 'Description can be at most 5000 characters.';
        }

        // Validate questions
        $questions = $input['questions'] ?? [];
        if (empty($questions) || !is_array($questions)) {
            $errors[] = 'Add at least one question.';
        }

        $hasVoice = false;
        foreach ($questions as $i => $q) {
            $no   = $i + 1;
            $type = $q['type'] ?? '';
            if (!in_array($type, self::VALID_QUESTION_TYPES, true)) {
                $errors[] = "Question #{$no}: Invalid type.";
                continue;
            }
            if (empty(trim($q['question_text'] ?? ''))) {
                $errors[] = "Question #{$no}: Content is required.";
            }
            if ($type === 'voice') {
                $hasVoice = true;
            } else {
                // MCQ: at least 2 options and 1 correct option
                $opts    = $q['options'] ?? [];
                $correct = array_filter($opts, fn($o) => !empty($o['is_correct']));
                if (count($opts) < 2) {
                    $errors[] = "Question #{$no}: At least 2 options are required.";
                }
                if (count($correct) !== 1) {
                    $errors[] = "Question #{$no}: Mark exactly one correct option.";
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return [
            'title'       => $title,
            'description' => $description !== '' ? $description : null,
            'status'      => $status,
            'questions'   => $questions,
        ];
    }

    /**
     * Save questions and options in DB
     */
    private function saveQuestionsWithOptions(int $quizId, array $questions): void
    {
        foreach ($questions as $order => $q) {
            $questionId = $this->repo->saveQuestion([
                'quiz_id'       => $quizId,
                'type'          => $q['type'],
                'question_text' => trim($q['question_text']),
                'display_order' => $order,
            ]);

            if ($q['type'] !== 'voice' && !empty($q['options'])) {
                $this->repo->saveOptions($questionId, $q['options']);
            }
        }
    }

    /**
     * Determine file extension from MIME type
     */
    private function getExtensionFromMime(string $mime): string
    {
        return match ($mime) {
            'audio/webm'  => 'webm',
            'audio/ogg'   => 'ogg',
            'audio/wav'   => 'wav',
            'audio/mp4'   => 'mp4',
            'audio/mpeg'  => 'mp3',
            'audio/x-m4a' => 'm4a',
            default       => 'webm',
        };
    }
}
