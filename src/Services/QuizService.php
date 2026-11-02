<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QuizRepository;

/**
 * QuizService — Unified Quiz & Exam System Business Logic Layer
 *
 * Controller only takes input and sends output.
 * All validation, business rules, timing, scoring, and file handling happen here.
 */
class QuizService
{
    // Allowed voice MIME types
    private const ALLOWED_AUDIO_MIMES = [
        'audio/webm',
        'video/webm',
        'audio/ogg',
        'video/ogg',
        'audio/wav',
        'audio/mp4',
        'video/mp4',
        'audio/mpeg',
        'audio/x-m4a',
    ];

    // Maximum voice file size (10MB)
    private const MAX_VOICE_BYTES = 10 * 1024 * 1024;

    // Student voice storage base path
    private const VOICE_STORAGE_BASE = __DIR__ . '/../../storage/quiz_voices';

    // Admin review voice storage base path
    private const ADMIN_VOICE_STORAGE_BASE = __DIR__ . '/../../storage/admin_review_voices';

    // Question types
    private const VALID_QUESTION_TYPES = ['letter', 'pronunciation', 'voice', 'mcq', 'multiple_choice'];

    // Quiz types
    private const VALID_QUIZ_TYPES = ['quiz', 'exam'];

    public function __construct(private QuizRepository $repo) {}

    // ================================================================
    // ADMIN: QUIZ & EXAM MANAGEMENT
    // ================================================================

    /**
     * Create new quiz or exam (validation + DB save, in transaction)
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
                'title'            => $validated['title'],
                'type'             => $validated['type'],
                'duration_minutes' => $validated['duration_minutes'],
                'attempt_limit'    => $validated['attempt_limit'],
                'total_marks'      => $validated['total_marks'],
                'pass_marks'       => $validated['pass_marks'],
                'custom_fields'    => $validated['custom_fields'],
                'description'      => $validated['description'],
                'status'           => $validated['status'],
                'created_by'       => $adminId,
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
     * Update quiz/exam (replaces all questions)
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
                'title'            => $validated['title'],
                'type'             => $validated['type'],
                'duration_minutes' => $validated['duration_minutes'],
                'attempt_limit'    => $validated['attempt_limit'],
                'total_marks'      => $validated['total_marks'],
                'pass_marks'       => $validated['pass_marks'],
                'custom_fields'    => $validated['custom_fields'],
                'description'      => $validated['description'],
                'status'           => $validated['status'],
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

        // Single JOIN query resolves N+1 query problem
        $quiz['questions'] = $this->repo->getQuestionsWithOptionsByQuizId($quizId);
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

        // Add score percentage and pass status to each attempt
        foreach ($attempts as &$a) {
            $totalMarks = (float) ($a['total_marks'] > 0 ? $a['total_marks'] : $a['total_questions']);
            $obtained = (float) $a['obtained_marks'];
            $a['score_pct'] = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 1) : 0;
            $a['is_passed'] = ($quiz['pass_marks'] > 0) ? ($obtained >= (float) $quiz['pass_marks']) : null;
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
     * Set voice review status
     */
    public function toggleVoiceReview(int $attemptId, bool $isReviewed): void
    {
        $this->repo->toggleVoiceReview($attemptId, $isReviewed);
    }

    /**
     * Sidebar badge count
     */
    public function getUnreviewedVoiceCount(): int
    {
        return $this->repo->getGlobalUnreviewedVoiceCount();
    }

    /**
     * Mark all notifications read for a quiz report
     */
    public function markAllNotified(int $quizId): void
    {
        $this->repo->markAllNotified($quizId);
    }

    /**
     * Find attempt details by ID
     */
    public function getAttemptById(int $attemptId): ?array
    {
        return $this->repo->findAttemptById($attemptId);
    }

    // ================================================================
    // PUBLIC: REGISTRATION & PARTICIPATION
    // ================================================================

    /**
     * Participant form validation with dynamic custom fields
     *
     * @return array validated & sanitized data
     * @throws \InvalidArgumentException
     */
    public function validateParticipant(array $input, array $customFieldDefs = []): array
    {
        $errors = [];

        // Name
        $name = trim($input['participant_name'] ?? '');
        if ($name === '') {
            $errors[] = 'Name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'Name can be a maximum of 150 characters.';
        }

        // Gender — whitelist
        $gender = trim($input['gender'] ?? '');
        if (!in_array($gender, ['male', 'female'], true)) {
            $errors[] = 'Please select a valid gender.';
        }

        // WhatsApp Number
        $whatsapp = trim($input['whatsapp_number'] ?? '');
        if ($whatsapp === '') {
            $errors[] = 'WhatsApp number is required.';
        } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $whatsapp)) {
            $errors[] = 'Please provide a valid 10-15 digit WhatsApp number.';
        }

        // Email — optional
        $email = trim($input['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if ($email !== '' && mb_strlen($email) > 150) {
            $errors[] = 'Email can be a maximum of 150 characters.';
        }

        // Custom Fields Validation
        $customData = [];
        $submittedCustom = $input['custom_fields'] ?? [];
        if (!empty($customFieldDefs) && is_array($customFieldDefs)) {
            foreach ($customFieldDefs as $field) {
                $key      = trim($field['key'] ?? '');
                $label    = trim($field['label'] ?? $key);
                $required = !empty($field['required']);
                $val      = trim($submittedCustom[$key] ?? '');

                if ($required && $val === '') {
                    $errors[] = "{$label} is required.";
                }
                if ($val !== '') {
                    $customData[$key] = mb_substr($val, 0, 255);
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return [
            'participant_name'   => $name,
            'gender'             => $gender,
            'whatsapp_number'    => $whatsapp,
            'email'              => $email !== '' ? $email : null,
            'custom_fields_data' => $customData,
        ];
    }

    /**
     * Start Quiz or Resume in_progress Attempt:
     * - Check attempt limit
     * - If existing non-expired in_progress attempt exists → resume it
     * - Else create fresh attempt with expires_at calculated
     *
     * @return array ['token' => string, 'attempt_id' => int, 'resumed' => bool]
     * @throws \RuntimeException
     */
    public function startOrResumeAttempt(int $quizId, array $participant): array
    {
        $quiz = $this->repo->findActiveQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('The quiz was not found or is currently unavailable.');
        }

        $phone = $participant['whatsapp_number'];
        $attemptLimit = (int) ($quiz['attempt_limit'] ?? 1);

        // Check if user has already exhausted attempt limit
        if ($attemptLimit > 0) {
            $completedCount = $this->repo->countCompletedAttempts($quizId, $phone);
            if ($completedCount >= $attemptLimit) {
                throw new \RuntimeException(
                    "You have already completed the maximum allowed attempts ({$attemptLimit}) for this quiz."
                );
            }
        }

        // Number of questions & total marks
        $questions  = $this->repo->getQuestionsByQuizId($quizId);
        $totalCount = count($questions);
        $totalMarks = (float) ($quiz['total_marks'] ?? 0.00);
        if ($totalMarks <= 0.00) {
            $totalMarks = (float) $totalCount;
        }

        // Calculate expires_at based on duration_minutes
        $durationMinutes = (int) ($quiz['duration_minutes'] ?? 0);
        $expiresAt = null;
        if ($durationMinutes > 0) {
            $expiresAt = date('Y-m-d H:i:s', time() + ($durationMinutes * 60));
        }

        // Check existing in_progress attempt
        $existing = $this->repo->findInProgressAttempt($quizId, $phone);

        if ($existing) {
            // Check if existing in_progress attempt has already expired
            if (!empty($existing['expires_at']) && strtotime($existing['expires_at']) < time()) {
                // Auto-finalize the expired attempt
                $this->finalizeAttempt((int) $existing['id'], autoSubmitted: true);

                // Re-verify limit after auto-finalization
                if ($attemptLimit > 0) {
                    $completedCount = $this->repo->countCompletedAttempts($quizId, $phone);
                    if ($completedCount >= $attemptLimit) {
                        throw new \RuntimeException(
                            "Your previous exam time has expired and your results have been submitted."
                        );
                    }
                }
            } else {
                // Not expired → RESUME ongoing attempt!
                return [
                    'token'      => $existing['session_token'],
                    'attempt_id' => (int) $existing['id'],
                    'resumed'    => true,
                ];
            }
        }

        // Create new attempt
        $completedAttempts = $this->repo->countCompletedAttempts($quizId, $phone);
        $attemptNumber     = $completedAttempts + 1;
        $newToken          = bin2hex(random_bytes(32));

        $attemptId = $this->repo->createAttempt([
            'quiz_id'            => $quizId,
            'participant_name'   => $participant['participant_name'],
            'gender'             => $participant['gender'],
            'whatsapp_number'    => $participant['whatsapp_number'],
            'attempt_number'     => $attemptNumber,
            'custom_fields_data' => $participant['custom_fields_data'] ?? [],
            'email'              => $participant['email'],
            'total_questions'    => $totalCount,
            'total_marks'        => $totalMarks,
            'session_token'      => $newToken,
            'expires_at'         => $expiresAt,
        ]);

        return [
            'token'      => $newToken,
            'attempt_id' => $attemptId,
            'resumed'    => false,
        ];
    }

    // ================================================================
    // PUBLIC: QUIZ PLAY & AUTO-SUBMISSION
    // ================================================================

    /**
     * Load questions for Quiz player
     * Options: shuffle on every request for MCQ
     */
    public function getQuestionsForPlayer(int $quizId): array
    {
        // Single JOIN query resolves N+1 query problem on public endpoint
        $questions = $this->repo->getQuestionsWithOptionsByQuizId($quizId);

        // Ensure natural ordering or group by type
        $typeOrder = ['letter' => 0, 'pronunciation' => 1, 'mcq' => 2, 'multiple_choice' => 3, 'voice' => 4];
        usort($questions, fn($a, $b) =>
            ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99)
        );

        foreach ($questions as &$q) {
            if ($q['type'] !== 'voice' && !empty($q['options'])) {
                shuffle($q['options']); // Random option order each time
                // Security: Strip is_correct so candidates cannot inspect devtools to cheat
                foreach ($q['options'] as &$opt) {
                    unset($opt['is_correct']);
                }
                unset($opt);
            }
        }
        unset($q);

        return $questions;
    }

    /**
     * Verify and save answer
     * Supports single choice and multiple choice with score calculation
     *
     * @return array ['correct' => bool, 'correct_option_id' => ?int, 'marks_awarded' => float]
     * @throws \RuntimeException if attempt, question, or timer is expired
     */
    public function submitMCQAnswer(
        int    $attemptId,
        int    $questionId,
        int    $selectedOptionId,
        int    $quizId,
        ?string $token = null
    ): array {
        // If token is provided, verify timer has not expired
        if ($token !== null) {
            $attempt = $this->repo->findAttemptByToken($token);
            if ($attempt && !empty($attempt['expires_at'])) {
                $expiresTime = strtotime($attempt['expires_at']);
                // 15 seconds grace period for network latency
                if (time() > ($expiresTime + 15)) {
                    $this->finalizeAttempt($attemptId, autoSubmitted: true);
                    throw new \RuntimeException('Time limit has expired. The quiz has been automatically submitted.');
                }
            }
        }

        // Get question details to check marks
        $questions = $this->repo->getQuestionsByQuizId($quizId);
        $qMeta = null;
        foreach ($questions as $q) {
            if ((int) $q['id'] === $questionId) {
                $qMeta = $q;
                break;
            }
        }

        $qMarks = (float) ($qMeta['marks'] ?? 1.00);

        // Get correct answer from DB (client will not be trusted)
        $correctOption = $this->repo->getCorrectOptionForQuestion($questionId);
        if (!$correctOption) {
            throw new \RuntimeException('Question not found.');
        }

        $isCorrect = ((int) $correctOption['id'] === $selectedOptionId);
        $marksAwarded = $isCorrect ? $qMarks : 0.00;

        $this->repo->recordAnswer(
            $attemptId,
            $questionId,
            $selectedOptionId,
            $isCorrect,
            $marksAwarded
        );

        return [
            'correct'           => $isCorrect,
            'is_correct'        => $isCorrect,
            'correct_option_id' => (int) $correctOption['id'],
            'marks_awarded'     => $marksAwarded,
        ];
    }

    /**
     * Finalize attempt — calculate final scores
     */
    public function finalizeAttempt(int $attemptId, bool $autoSubmitted = false): array
    {
        $attempt = $this->repo->findAttemptById($attemptId);
        if (!$attempt) {
            throw new \RuntimeException('Attempt not found.');
        }

        $stats = $this->repo->getAnswerStats($attemptId);
        $correct = (int) ($stats['correct_count'] ?? 0);
        $total   = (int) ($attempt['total_questions'] ?? 0);
        $obtainedMarks = (float) ($stats['total_marks_awarded'] ?? 0.00);
        $totalMarks = (float) ($attempt['total_marks'] ?? 0.00);

        if ($totalMarks <= 0.00 && $total > 0) {
            $totalMarks = (float) $total;
        }

        $this->repo->finalizeAttempt(
            $attemptId,
            $correct,
            $total,
            $obtainedMarks,
            $totalMarks,
            $autoSubmitted
        );

        return [
            'correct_answers' => $correct,
            'total_questions' => $total,
            'obtained_marks'  => $obtainedMarks,
            'total_marks'     => $totalMarks,
            'score_pct'       => $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 1) : 0,
            'auto_submitted'  => $autoSubmitted,
        ];
    }

    /**
     * Auto-submit triggered when countdown timer reaches 00:00
     */
    public function autoSubmitAttempt(string $token): array
    {
        $attempt = $this->repo->findAttemptByToken($token);
        if (!$attempt) {
            throw new \RuntimeException('Attempt not found.');
        }

        if ($attempt['status'] === 'completed' || $attempt['status'] === 'voice_submitted') {
            return [
                'already_completed' => true,
                'redirect'          => '/quiz/result.php?t=' . urlencode($token),
            ];
        }

        $scoreData = $this->finalizeAttempt((int) $attempt['id'], autoSubmitted: true);

        return [
            'success'  => true,
            'redirect' => '/quiz/result.php?t=' . urlencode($token),
            'score'    => $scoreData,
        ];
    }

    /**
     * Upload student voice recording
     *
     * @throws \InvalidArgumentException if file is invalid
     * @throws \RuntimeException on save failure
     */
    public function uploadVoiceRecording(int $attemptId, array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('File upload failed. Please try again.');
        }

        if ($file['size'] > self::MAX_VOICE_BYTES) {
            throw new \InvalidArgumentException('Voice file can be at most 10 MB.');
        }

        // Validate MIME with finfo
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_AUDIO_MIMES, true)) {
            throw new \InvalidArgumentException('Not an allowed audio format.');
        }

        $ext      = $this->getExtensionFromMime($mimeType);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        $dirPath = self::VOICE_STORAGE_BASE . '/' . date('Y/m');
        if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
            throw new \RuntimeException('Could not create storage directory.');
        }

        $fullPath = $dirPath . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Could not save file.');
        }

        $relativePath = 'quiz_voices/' . date('Y/m') . '/' . $filename;
        $saved = $this->repo->saveVoiceSubmission($attemptId, $relativePath);

        if (!$saved) {
            @unlink($fullPath);
            throw new \RuntimeException('Could not save data.');
        }

        return $relativePath;
    }

    /**
     * Upload Admin voice review recording
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function uploadAdminVoiceReview(
        int     $attemptId,
        array   $file,
        ?float  $voiceMarks = null,
        ?string $voiceNote = null
    ): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('File upload failed. Please try again.');
        }

        if ($file['size'] > self::MAX_VOICE_BYTES) {
            throw new \InvalidArgumentException('Voice review file can be at most 10 MB.');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_AUDIO_MIMES, true)) {
            throw new \InvalidArgumentException('Not an allowed audio format.');
        }

        $ext      = $this->getExtensionFromMime($mimeType);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        $dirPath = self::ADMIN_VOICE_STORAGE_BASE . '/' . date('Y/m');
        if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
            throw new \RuntimeException('Could not create admin voice storage directory.');
        }

        $fullPath = $dirPath . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Could not save admin voice review file.');
        }

        $relativePath = 'admin_review_voices/' . date('Y/m') . '/' . $filename;
        $saved = $this->repo->saveAdminVoiceReview($attemptId, $relativePath, $voiceMarks, $voiceNote);

        if (!$saved) {
            @unlink($fullPath);
            throw new \RuntimeException('Could not update admin voice review data.');
        }

        return $relativePath;
    }

    /**
     * Generate WhatsApp One-Click Share URL with formatted Bengali message
     */
    public function generateWhatsAppShareUrl(int $attemptId, string $baseUrl = ''): string
    {
        $attempt = $this->repo->findAttemptById($attemptId);
        if (!$attempt) {
            throw new \RuntimeException('Attempt not found.');
        }

        $phone = preg_replace('/[^0-9]/', '', $attempt['whatsapp_number']);
        if (str_starts_with($phone, '01') && strlen($phone) === 11) {
            $phone = '88' . $phone;
        }

        $name     = $attempt['participant_name'];
        $title    = $attempt['quiz_title'] ?? 'Quiz';
        $obtained = number_format((float) $attempt['obtained_marks'], 2);
        $total    = number_format((float) $attempt['total_marks'], 2);
        $correct  = (int) $attempt['correct_answers'];
        $totalQ   = (int) $attempt['total_questions'];

        $cleanBase = rtrim($baseUrl, '/');
        if (empty($cleanBase)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $cleanBase = $protocol . '://' . $host;
        }

        $reviewAudioUrl = '';
        if (!empty($attempt['admin_voice_file_path'])) {
            $reviewAudioUrl = $cleanBase . '/quiz/listen_review.php?t=' . urlencode($attempt['session_token']);
        }

        $msg = "Assalamu Alaikum {$name},\n\n";
        $msg .= "Your results and evaluation for \"{$title}\":\n";
        $msg .= "• Total Marks Obtained: {$obtained} / {$total}\n";
        $msg .= "• Correct Answers: {$correct} / {$totalQ}\n";

        if ($attempt['voice_marks'] !== null) {
            $msg .= "• Teacher Voice / Oral Marks: " . number_format((float) $attempt['voice_marks'], 2) . "\n";
        }

        if (!empty($reviewAudioUrl)) {
            $msg .= "\nClick the link below to listen to the teacher's voice review:\n{$reviewAudioUrl}\n";
        }

        if (!empty($attempt['voice_note'])) {
            $msg .= "\nTeacher Note: {$attempt['voice_note']}\n";
        }

        $msg .= "\nThank you,\nRahe Nazat Institute";

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($msg);
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

        // If the attempt is still in_progress (e.g. no voice question), finalize it now
        if ($attempt['status'] === 'in_progress') {
            $this->finalizeAttempt((int) $attempt['id']);
            $attempt = $this->repo->findAttemptByToken($token);
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
     * Quiz & Exam input validation (used in create & update)
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

        $type = trim($input['type'] ?? 'quiz');
        if (!in_array($type, self::VALID_QUIZ_TYPES, true)) {
            $type = 'quiz';
        }

        $durationMinutes = max(0, (int) ($input['duration_minutes'] ?? 0));
        $attemptLimit    = max(0, (int) ($input['attempt_limit'] ?? 1));
        $passMarks       = max(0.00, (float) ($input['pass_marks'] ?? 0.00));

        $status = $input['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $description = trim($input['description'] ?? '');
        if (mb_strlen($description) > 5000) {
            $errors[] = 'Description can be at most 5000 characters.';
        }

        // Custom fields validation
        $customFields = [];
        if (!empty($input['custom_fields'])) {
            $rawCustom = is_string($input['custom_fields'])
                ? json_decode($input['custom_fields'], true)
                : $input['custom_fields'];

            if (is_array($rawCustom)) {
                foreach ($rawCustom as $f) {
                    $key   = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($f['key'] ?? '')));
                    $label = trim($f['label'] ?? '');
                    if ($key !== '' && $label !== '') {
                        $customFields[] = [
                            'key'      => $key,
                            'label'    => $label,
                            'type'     => in_array($f['type'] ?? 'text', ['text', 'number', 'select'], true) ? $f['type'] : 'text',
                            'required' => !empty($f['required']),
                        ];
                    }
                }
            }
        }

        // Validate questions
        $questions = $input['questions'] ?? [];
        if (empty($questions) || !is_array($questions)) {
            $errors[] = 'Add at least one question.';
        }

        $totalMarks = 0.00;
        foreach ($questions as $i => $q) {
            $no    = $i + 1;
            $qType = $q['type'] ?? '';
            if (!in_array($qType, self::VALID_QUESTION_TYPES, true)) {
                $errors[] = "Question #{$no}: Invalid question format.";
                continue;
            }
            if (empty(trim($q['question_text'] ?? ''))) {
                $errors[] = "Question #{$no}: Content is required.";
            }

            $marks = max(0.00, (float) ($q['marks'] ?? 1.00));
            $totalMarks += $marks;

            if ($qType !== 'voice') {
                $opts    = $q['options'] ?? [];
                $correct = array_filter($opts, fn($o) => !empty($o['is_correct']));
                if (count($opts) < 2) {
                    $errors[] = "Question #{$no}: At least 2 options are required.";
                }
                if ($qType === 'multiple_choice') {
                    if (count($correct) < 1) {
                        $errors[] = "Question #{$no}: Mark at least one correct option.";
                    }
                } else {
                    if (count($correct) !== 1) {
                        $errors[] = "Question #{$no}: Mark exactly one correct option.";
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return [
            'title'            => $title,
            'type'             => $type,
            'duration_minutes' => $durationMinutes,
            'attempt_limit'    => $attemptLimit,
            'total_marks'      => $totalMarks,
            'pass_marks'       => $passMarks,
            'custom_fields'    => !empty($customFields) ? $customFields : null,
            'description'      => $description !== '' ? $description : null,
            'status'           => $status,
            'questions'        => $questions,
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
                'marks'         => (float) ($q['marks'] ?? 1.00),
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
            'audio/webm', 'video/webm' => 'webm',
            'audio/ogg', 'video/ogg'   => 'ogg',
            'audio/wav'                => 'wav',
            'audio/mp4', 'video/mp4'   => 'mp4',
            'audio/mpeg'               => 'mp3',
            'audio/x-m4a'              => 'm4a',
            default                    => 'webm',
        };
    }
}
