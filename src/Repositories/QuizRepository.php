<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * QuizRepository — Unified Quiz & Exam System Data Access Layer
 *
 * All DB queries here. Controller or Service will never write SQL directly.
 * Each method: PDO Prepared Statement, strictly parameterized, no string concatenation.
 */
class QuizRepository
{
    public function __construct(private PDO $db) {}

    // ================================================================
    // TRANSACTION HELPERS
    // ================================================================

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void           { $this->db->commit(); }
    public function rollback(): void         { $this->db->rollBack(); }

    // ================================================================
    // QUIZ & EXAM CRUD
    // ================================================================

    /**
     * Creates a new quiz/exam — returns quiz id
     */
    public function createQuiz(array $data): int
    {
        $customFields = null;
        if (!empty($data['custom_fields'])) {
            $customFields = is_string($data['custom_fields'])
                ? $data['custom_fields']
                : json_encode($data['custom_fields'], JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO quizzes 
                (title, type, duration_minutes, attempt_limit, total_marks, pass_marks, 
                 custom_fields, description, status, created_by)
             VALUES 
                (:title, :type, :duration_minutes, :attempt_limit, :total_marks, :pass_marks, 
                 :custom_fields, :description, :status, :created_by)'
        );
        $stmt->execute([
            ':title'            => $data['title'],
            ':type'             => $data['type'] ?? 'quiz',
            ':duration_minutes' => (int) ($data['duration_minutes'] ?? 0),
            ':attempt_limit'    => (int) ($data['attempt_limit'] ?? 1),
            ':total_marks'      => (float) ($data['total_marks'] ?? 0.00),
            ':pass_marks'       => (float) ($data['pass_marks'] ?? 0.00),
            ':custom_fields'    => $customFields,
            ':description'      => $data['description'] ?? null,
            ':status'           => $data['status'] ?? 'active',
            ':created_by'       => $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update quiz/exam
     */
    public function updateQuiz(int $id, array $data): bool
    {
        $customFields = null;
        if (array_key_exists('custom_fields', $data)) {
            $customFields = is_string($data['custom_fields'])
                ? $data['custom_fields']
                : (is_array($data['custom_fields']) ? json_encode($data['custom_fields'], JSON_UNESCAPED_UNICODE) : null);
        }

        $stmt = $this->db->prepare(
            'UPDATE quizzes
             SET title = :title,
                 type = :type,
                 duration_minutes = :duration_minutes,
                 attempt_limit = :attempt_limit,
                 total_marks = :total_marks,
                 pass_marks = :pass_marks,
                 custom_fields = :custom_fields,
                 description = :description,
                 status = :status
             WHERE id = :id AND deleted_at IS NULL'
        );
        return $stmt->execute([
            ':title'            => $data['title'],
            ':type'             => $data['type'] ?? 'quiz',
            ':duration_minutes' => (int) ($data['duration_minutes'] ?? 0),
            ':attempt_limit'    => (int) ($data['attempt_limit'] ?? 1),
            ':total_marks'      => (float) ($data['total_marks'] ?? 0.00),
            ':pass_marks'       => (float) ($data['pass_marks'] ?? 0.00),
            ':custom_fields'    => $customFields,
            ':description'      => $data['description'] ?? null,
            ':status'           => $data['status'],
            ':id'               => $id,
        ]);
    }

    /**
     * Soft delete
     */
    public function softDeleteQuiz(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quizzes SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL'
        );
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find a quiz by ID (excluding soft-deleted)
     */
    public function findQuizById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, type, duration_minutes, attempt_limit, total_marks, pass_marks,
                    custom_fields, description, status, created_by, created_at, updated_at
             FROM quizzes
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['custom_fields']) && is_string($row['custom_fields'])) {
            $row['custom_fields'] = json_decode($row['custom_fields'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Active quiz — for public page (direct link)
     */
    public function findActiveQuizById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, type, duration_minutes, attempt_limit, total_marks, pass_marks,
                    custom_fields, description
             FROM quizzes
             WHERE id = :id AND status = "active" AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['custom_fields']) && is_string($row['custom_fields'])) {
            $row['custom_fields'] = json_decode($row['custom_fields'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Paginated quiz list for Admin
     */
    public function findAllQuizzes(array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = 'WHERE q.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['status'])) {
            $where .= ' AND q.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where .= ' AND q.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND q.title LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT q.id, q.title, q.type, q.duration_minutes, q.attempt_limit, q.total_marks, q.pass_marks,
                    q.status, q.created_at,
                    COUNT(DISTINCT qa.id)                               AS total_attempts,
                    SUM(qa.voice_submitted = 1)                         AS voice_count,
                    SUM(qa.voice_submitted = 1 AND qa.voice_reviewed = 0) AS unreviewed_count
             FROM quizzes q
             LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id
             {$where}
             GROUP BY q.id
             ORDER BY q.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total number of Admin quiz list (for pagination)
     */
    public function countAllQuizzes(array $filters): int
    {
        $where  = 'WHERE deleted_at IS NULL';
        $params = [];

        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where .= ' AND type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND title LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare("SELECT COUNT(id) FROM quizzes {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // ================================================================
    // QUESTIONS & OPTIONS
    // ================================================================

    /**
     * Save new question — returns question id
     */
    public function saveQuestion(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO quiz_questions (quiz_id, type, question_text, marks, display_order)
             VALUES (:quiz_id, :type, :question_text, :marks, :display_order)'
        );
        $stmt->execute([
            ':quiz_id'       => $data['quiz_id'],
            ':type'          => $data['type'],
            ':question_text' => $data['question_text'],
            ':marks'         => (float) ($data['marks'] ?? 1.00),
            ':display_order' => $data['display_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Save all options for a question (batch insert)
     */
    public function saveOptions(int $questionId, array $options): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO quiz_options (question_id, option_text, is_correct, option_order)
             VALUES (:question_id, :option_text, :is_correct, :option_order)'
        );
        foreach ($options as $order => $opt) {
            $stmt->execute([
                ':question_id' => $questionId,
                ':option_text'  => $opt['text'] ?? $opt['option_text'] ?? '',
                ':is_correct'  => (int) ($opt['is_correct'] ?? 0),
                ':option_order' => $order,
            ]);
        }
    }

    /**
     * Delete all questions for a quiz (before replacing entirely in edit)
     */
    public function deleteQuestionsByQuizId(int $quizId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM quiz_questions WHERE quiz_id = :quiz_id'
        );
        $stmt->execute([':quiz_id' => $quizId]);
    }

    /**
     * All questions for a quiz (by display_order)
     */
    public function getQuestionsByQuizId(int $quizId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, type, question_text, marks, display_order
             FROM quiz_questions
             WHERE quiz_id = :quiz_id
             ORDER BY display_order ASC'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all questions and their options for a quiz in a single JOIN query,
     * eliminating the N+1 query problem.
     *
     * @param int $quizId
     * @return array Array of questions, each with an 'options' sub-array
     */
    public function getQuestionsWithOptionsByQuizId(int $quizId): array
    {
        $stmt = $this->db->prepare(
            'SELECT q.id AS question_id, q.type, q.question_text, q.marks, q.display_order,
                    o.id AS option_id, o.option_text, o.is_correct, o.option_order
             FROM quiz_questions q
             LEFT JOIN quiz_options o ON o.question_id = q.id
             WHERE q.quiz_id = :quiz_id
             ORDER BY q.display_order ASC, o.option_order ASC, o.id ASC'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $questions = [];
        foreach ($rows as $row) {
            $qId = (int) $row['question_id'];
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    'id'            => $qId,
                    'type'          => $row['type'],
                    'question_text' => $row['question_text'],
                    'marks'         => (float) $row['marks'],
                    'display_order' => (int) $row['display_order'],
                    'options'       => [],
                ];
            }
            if ($row['option_id'] !== null) {
                $questions[$qId]['options'][] = [
                    'id'           => (int) $row['option_id'],
                    'option_text'  => $row['option_text'],
                    'is_correct'   => (int) $row['is_correct'],
                    'option_order' => (int) $row['option_order'],
                ];
            }
        }

        return array_values($questions);
    }

    /**
     * All options for a question (by option_order)
     */
    public function getOptionsForQuestion(int $questionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, option_text, is_correct, option_order
             FROM quiz_options
             WHERE question_id = :question_id
             ORDER BY option_order ASC'
        );
        $stmt->execute([':question_id' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verify correct answer of an option (single choice)
     */
    public function getCorrectOptionForQuestion(int $questionId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, option_text
             FROM quiz_options
             WHERE question_id = :question_id AND is_correct = 1
             LIMIT 1'
        );
        $stmt->execute([':question_id' => $questionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * All correct options for a question (multiple choice)
     */
    public function getCorrectOptionsForQuestion(int $questionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, option_text
             FROM quiz_options
             WHERE question_id = :question_id AND is_correct = 1
             ORDER BY option_order ASC'
        );
        $stmt->execute([':question_id' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================================================================
    // QUIZ ATTEMPTS & EXAM RUNTIME
    // ================================================================

    /**
     * Count completed attempts for a phone number in a quiz
     */
    public function countCompletedAttempts(int $quizId, string $phone): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(id) FROM quiz_attempts
             WHERE quiz_id = :quiz_id
               AND whatsapp_number = :phone
               AND status IN ("completed", "voice_submitted")'
        );
        $stmt->execute([':quiz_id' => $quizId, ':phone' => $phone]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Check if user is blocked based on attempt limit
     */
    public function isPhoneBlockedForQuiz(int $quizId, string $phone, int $attemptLimit = 1): bool
    {
        if ($attemptLimit <= 0) {
            return false; // Unlimited attempts
        }
        return ($this->countCompletedAttempts($quizId, $phone) >= $attemptLimit);
    }

    /**
     * Find in_progress attempt (will be resumed or reset on retry)
     */
    public function findInProgressAttempt(int $quizId, string $phone): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, session_token, started_at, expires_at, attempt_number
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id
               AND whatsapp_number = :phone
               AND status = "in_progress"
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([':quiz_id' => $quizId, ':phone' => $phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create new attempt
     */
    public function createAttempt(array $data): int
    {
        $customData = null;
        if (!empty($data['custom_fields_data'])) {
            $customData = is_string($data['custom_fields_data'])
                ? $data['custom_fields_data']
                : json_encode($data['custom_fields_data'], JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO quiz_attempts
                (quiz_id, participant_name, gender, whatsapp_number, attempt_number,
                 custom_fields_data, email, status, total_questions, total_marks,
                 obtained_marks, session_token, started_at, expires_at)
             VALUES
                (:quiz_id, :name, :gender, :whatsapp, :attempt_number,
                 :custom_fields_data, :email, "in_progress", :total_questions, :total_marks,
                 0.00, :token, NOW(), :expires_at)'
        );
        $stmt->execute([
            ':quiz_id'            => $data['quiz_id'],
            ':name'               => $data['participant_name'],
            ':gender'             => $data['gender'],
            ':whatsapp'           => $data['whatsapp_number'],
            ':attempt_number'     => (int) ($data['attempt_number'] ?? 1),
            ':custom_fields_data' => $customData,
            ':email'              => $data['email'] ?? null,
            ':total_questions'    => (int) ($data['total_questions'] ?? 0),
            ':total_marks'        => (float) ($data['total_marks'] ?? 0.00),
            ':token'              => $data['session_token'],
            ':expires_at'         => $data['expires_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Reset attempt (fresh start with new token)
     */
    public function resetAttempt(
        int $attemptId,
        string $newToken,
        int $totalQuestions,
        float $totalMarks = 0.00,
        ?string $expiresAt = null
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET status = "in_progress",
                 correct_answers = 0,
                 obtained_marks = 0.00,
                 total_questions = :total,
                 total_marks = :total_marks,
                 session_token = :token,
                 started_at = NOW(),
                 expires_at = :expires_at,
                 completed_at = NULL,
                 auto_submitted = 0
             WHERE id = :id AND status != "voice_submitted"'
        );
        return $stmt->execute([
            ':total'       => $totalQuestions,
            ':total_marks' => $totalMarks,
            ':token'       => $newToken,
            ':expires_at'  => $expiresAt,
            ':id'          => $attemptId,
        ]);
    }

    /**
     * Delete answers for old attempt (during retry/reset)
     */
    public function deleteAnswersForAttempt(int $attemptId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM quiz_answers WHERE attempt_id = :attempt_id'
        );
        $stmt->execute([':attempt_id' => $attemptId]);
    }

    /**
     * Find attempt by Token (includes quiz details and remaining seconds)
     */
    public function findAttemptByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT qa.id, qa.quiz_id, qa.participant_name, qa.gender, qa.whatsapp_number,
                    qa.attempt_number, qa.custom_fields_data, qa.email, qa.status,
                    qa.total_questions, qa.correct_answers, qa.total_marks, qa.obtained_marks,
                    qa.voice_submitted, qa.voice_file_path, qa.admin_voice_file_path,
                    qa.voice_marks, qa.voice_reviewed, qa.voice_note, qa.admin_notified,
                    qa.session_token, qa.started_at, qa.expires_at, qa.completed_at, qa.auto_submitted,
                    q.title AS quiz_title, q.type AS quiz_type, q.duration_minutes, q.attempt_limit,
                    q.pass_marks,
                    TIMESTAMPDIFF(SECOND, NOW(), qa.expires_at) AS remaining_seconds
             FROM quiz_attempts qa
             INNER JOIN quizzes q ON q.id = qa.quiz_id
             WHERE qa.session_token = :token'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['custom_fields_data']) && is_string($row['custom_fields_data'])) {
            $row['custom_fields_data'] = json_decode($row['custom_fields_data'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Record answer (supports single and multiple choice marks)
     */
    public function recordAnswer(
        int    $attemptId,
        int    $questionId,
        ?int   $selectedOptionId,
        bool   $isCorrect,
        float  $marksAwarded = 0.00,
        ?array $selectedOptionIds = null
    ): void {
        $optionIdsJson = !empty($selectedOptionIds) ? json_encode($selectedOptionIds) : null;

        $stmt = $this->db->prepare(
            'INSERT INTO quiz_answers (attempt_id, question_id, selected_option_id, selected_option_ids, is_correct, marks_awarded)
             VALUES (:attempt_id, :question_id, :option_id, :option_ids, :is_correct, :marks_awarded)
             ON DUPLICATE KEY UPDATE
                selected_option_id = VALUES(selected_option_id),
                selected_option_ids = VALUES(selected_option_ids),
                is_correct = VALUES(is_correct),
                marks_awarded = VALUES(marks_awarded)'
        );
        $stmt->execute([
            ':attempt_id'     => $attemptId,
            ':question_id'    => $questionId,
            ':option_id'      => $selectedOptionId,
            ':option_ids'     => $optionIdsJson,
            ':is_correct'     => (int) $isCorrect,
            ':marks_awarded'  => $marksAwarded,
        ]);
    }

    /**
     * Update attempt score and status
     */
    public function finalizeAttempt(
        int   $attemptId,
        int   $correctCount,
        int   $total,
        float $obtainedMarks = 0.00,
        float $totalMarks = 0.00,
        bool  $autoSubmitted = false
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET status = "completed",
                 correct_answers = :correct,
                 total_questions = :total,
                 obtained_marks = :obtained_marks,
                 total_marks = :total_marks,
                 auto_submitted = :auto_submitted,
                 completed_at = COALESCE(completed_at, NOW())
             WHERE id = :id AND status != "voice_submitted"'
        );
        return $stmt->execute([
            ':correct'        => $correctCount,
            ':total'          => $total,
            ':obtained_marks' => $obtainedMarks,
            ':total_marks'    => $totalMarks,
            ':auto_submitted' => (int) $autoSubmitted,
            ':id'             => $attemptId,
        ]);
    }

    /**
     * Save student voice file path
     */
    public function saveVoiceSubmission(int $attemptId, string $filePath): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET voice_submitted = 1,
                 voice_file_path = :path,
                 status = "voice_submitted",
                 completed_at = COALESCE(completed_at, NOW())
             WHERE id = :id AND voice_submitted = 0'
        );
        return $stmt->execute([':path' => $filePath, ':id' => $attemptId]);
    }

    /**
     * Save admin voice review, marks, and notes
     */
    public function saveAdminVoiceReview(
        int    $attemptId,
        string $filePath,
        ?float $voiceMarks = null,
        ?string $voiceNote = null
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET admin_voice_file_path = :path,
                 voice_marks = :voice_marks,
                 voice_note = :voice_note,
                 voice_reviewed = 1,
                 admin_notified = 1,
                 obtained_marks = obtained_marks + COALESCE(:voice_marks_add, 0)
             WHERE id = :id'
        );
        return $stmt->execute([
            ':path'            => $filePath,
            ':voice_marks'     => $voiceMarks,
            ':voice_note'      => $voiceNote,
            ':voice_marks_add' => $voiceMarks,
            ':id'              => $attemptId,
        ]);
    }

    // ================================================================
    // ADMIN REPORT & STATS
    // ================================================================

    /**
     * Admin dashboard stats card for quiz/exam
     */
    public function getDashboardStats(int $quizId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(id)                                              AS total_participants,
                SUM(status IN ("completed", "voice_submitted"))        AS completed_count,
                SUM(status = "in_progress")                            AS abandoned_count,
                SUM(voice_submitted = 1 AND voice_reviewed = 0)        AS unreviewed_count,
                SUM(auto_submitted = 1)                                AS auto_submitted_count,
                ROUND(AVG(obtained_marks), 2)                          AS avg_obtained_marks,
                MAX(obtained_marks)                                    AS highest_obtained_marks,
                ROUND(
                    SUM(status IN ("completed", "voice_submitted")) / NULLIF(COUNT(id), 0) * 100, 1
                )                                                      AS completion_pct
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Paginated participant attempt list (admin)
     */
    public function getAttemptList(int $quizId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT id, participant_name, gender, whatsapp_number, attempt_number,
                    custom_fields_data, email, status, total_questions, correct_answers,
                    total_marks, obtained_marks, auto_submitted,
                    voice_submitted, voice_file_path, admin_voice_file_path, voice_marks,
                    voice_reviewed, voice_note, admin_notified, session_token,
                    started_at, expires_at, completed_at
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id
             ORDER BY started_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':quiz_id', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($attempts as &$a) {
            if (!empty($a['custom_fields_data']) && is_string($a['custom_fields_data'])) {
                $a['custom_fields_data'] = json_decode($a['custom_fields_data'], true) ?: [];
            }
        }
        unset($a);

        return $attempts;
    }

    /**
     * Total count of Attempt list
     */
    public function countAttempts(int $quizId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(id) FROM quiz_attempts WHERE quiz_id = :quiz_id'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Set voice_reviewed status and admin_notified = 1
     */
    public function toggleVoiceReview(int $attemptId, bool $isReviewed): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET voice_reviewed = :is_reviewed,
                 admin_notified = 1
             WHERE id = :id AND voice_submitted = 1'
        );
        return $stmt->execute([':is_reviewed' => (int) $isReviewed, ':id' => $attemptId]);
    }

    /**
     * Set all admin_notified = 0 attempts to 1 (badge clear)
     */
    public function markAllNotified(int $quizId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET admin_notified = 1
             WHERE quiz_id = :quiz_id AND admin_notified = 0 AND voice_submitted = 1'
        );
        $stmt->execute([':quiz_id' => $quizId]);
    }

    /**
     * New (unreviewed) voice count — for sidebar badge
     */
    public function getGlobalUnreviewedVoiceCount(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(id) FROM quiz_attempts
             WHERE voice_submitted = 1 AND admin_notified = 0'
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find attempt by ID (internal use)
     */
    public function findAttemptById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT qa.id, qa.quiz_id, qa.participant_name, qa.gender, qa.whatsapp_number,
                    qa.attempt_number, qa.custom_fields_data, qa.email, qa.status,
                    qa.total_questions, qa.correct_answers, qa.total_marks, qa.obtained_marks,
                    qa.voice_submitted, qa.voice_file_path, qa.admin_voice_file_path,
                    qa.voice_marks, qa.voice_reviewed, qa.voice_note, qa.admin_notified,
                    qa.session_token, qa.started_at, qa.expires_at, qa.completed_at, qa.auto_submitted,
                    q.title AS quiz_title, q.type AS quiz_type, q.duration_minutes
             FROM quiz_attempts qa
             INNER JOIN quizzes q ON q.id = qa.quiz_id
             WHERE qa.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['custom_fields_data']) && is_string($row['custom_fields_data'])) {
            $row['custom_fields_data'] = json_decode($row['custom_fields_data'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Number of correct/wrong answers and marks for an attempt
     */
    public function getAnswerStats(int $attemptId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(id)                          AS total_answered,
                SUM(is_correct = 1)                AS correct_count,
                SUM(is_correct = 0)                AS wrong_count,
                COALESCE(SUM(marks_awarded), 0.00) AS total_marks_awarded
             FROM quiz_answers
             WHERE attempt_id = :attempt_id'
        );
        $stmt->execute([':attempt_id' => $attemptId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Breakdown by type for Result page
     */
    public function getAnswerBreakdown(int $attemptId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                qq.type,
                COUNT(qa.id)                          AS total,
                SUM(qa.is_correct = 1)                AS correct,
                COALESCE(SUM(qa.marks_awarded), 0.00) AS marks_awarded,
                COALESCE(SUM(qq.marks), 0.00)         AS total_marks
             FROM quiz_answers qa
             INNER JOIN quiz_questions qq ON qq.id = qa.question_id
             WHERE qa.attempt_id = :attempt_id
             GROUP BY qq.type'
        );
        $stmt->execute([':attempt_id' => $attemptId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['type']] = [
                'total'         => (int) $row['total'],
                'correct'       => (int) $row['correct'],
                'marks_awarded' => (float) $row['marks_awarded'],
                'total_marks'   => (float) $row['total_marks'],
            ];
        }
        return $result;
    }
}
