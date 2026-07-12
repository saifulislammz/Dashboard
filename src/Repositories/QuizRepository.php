<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * QuizRepository — Arabic Quiz System Data Access Layer
 *
 * All DB queries here. Controller or Service will never write SQL directly.
 * Each method: PDO Prepared Statement, no string concatenation.
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
    // QUIZ CRUD
    // ================================================================

    /**
     * Creates a new quiz — returns quiz id
     */
    public function createQuiz(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO quizzes (title, description, status, created_by)
             VALUES (:title, :description, :status, :created_by)'
        );
        $stmt->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':status'      => $data['status'] ?? 'active',
            ':created_by'  => $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update quiz
     */
    public function updateQuiz(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quizzes
             SET title = :title, description = :description, status = :status
             WHERE id = :id AND deleted_at IS NULL'
        );
        return $stmt->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':status'      => $data['status'],
            ':id'          => $id,
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
            'SELECT id, title, description, status, created_by, created_at, updated_at
             FROM quizzes
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Active quiz — for public page (no list, direct link only)
     */
    public function findActiveQuizById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, description
             FROM quizzes
             WHERE id = :id AND status = "active" AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
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
        if (!empty($filters['search'])) {
            $where .= ' AND q.title LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT q.id, q.title, q.status, q.created_at,
                    COUNT(DISTINCT qa.id)                               AS total_attempts,
                    SUM(qa.voice_submitted = 1)                         AS voice_count,
                    SUM(qa.voice_submitted = 1 AND qa.admin_notified = 0) AS unreviewed_count
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
            'INSERT INTO quiz_questions (quiz_id, type, question_text, display_order)
             VALUES (:quiz_id, :type, :question_text, :display_order)'
        );
        $stmt->execute([
            ':quiz_id'       => $data['quiz_id'],
            ':type'          => $data['type'],
            ':question_text' => $data['question_text'],
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
                ':option_text' => $opt['text'],
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
            'SELECT id, type, question_text, display_order
             FROM quiz_questions
             WHERE quiz_id = :quiz_id
             ORDER BY display_order ASC'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Verify correct answer of an option
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

    // ================================================================
    // QUIZ ATTEMPTS (Guest)
    // ================================================================

    /**
     * Check if there is a voice_submitted attempt with this WhatsApp number in this quiz
     * (permanent block check)
     */
    public function isPhoneBlockedForQuiz(int $quizId, string $phone): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM quiz_attempts
             WHERE quiz_id = :quiz_id
               AND whatsapp_number = :phone
               AND voice_submitted = 1
             LIMIT 1'
        );
        $stmt->execute([':quiz_id' => $quizId, ':phone' => $phone]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Find in_progress attempt (will be reset on retry)
     */
    public function findInProgressAttempt(int $quizId, string $phone): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, session_token
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id
               AND whatsapp_number = :phone
               AND voice_submitted = 0
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
        $stmt = $this->db->prepare(
            'INSERT INTO quiz_attempts
                (quiz_id, participant_name, gender, whatsapp_number, email,
                 status, total_questions, session_token)
             VALUES
                (:quiz_id, :name, :gender, :whatsapp, :email,
                 "in_progress", :total_questions, :token)'
        );
        $stmt->execute([
            ':quiz_id'         => $data['quiz_id'],
            ':name'            => $data['participant_name'],
            ':gender'          => $data['gender'],
            ':whatsapp'        => $data['whatsapp_number'],
            ':email'           => $data['email'] ?? null,
            ':total_questions' => $data['total_questions'],
            ':token'           => $data['session_token'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Retry: reset old in_progress attempt and start with a new token
     */
    public function resetAttempt(int $attemptId, string $newToken, int $totalQuestions): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET status = "in_progress",
                 correct_answers = 0,
                 total_questions = :total,
                 session_token = :token,
                 started_at = NOW(),
                 completed_at = NULL
             WHERE id = :id AND voice_submitted = 0'
        );
        return $stmt->execute([
            ':total' => $totalQuestions,
            ':token' => $newToken,
            ':id'    => $attemptId,
        ]);
    }

    /**
     * Delete answers for old attempt (during retry)
     */
    public function deleteAnswersForAttempt(int $attemptId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM quiz_answers WHERE attempt_id = :attempt_id'
        );
        $stmt->execute([':attempt_id' => $attemptId]);
    }

    /**
     * Find attempt by Token
     */
    public function findAttemptByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, quiz_id, participant_name, gender, whatsapp_number, email,
                    status, total_questions, correct_answers,
                    voice_submitted, voice_file_path, voice_note,
                    session_token, started_at, completed_at
             FROM quiz_attempts
             WHERE session_token = :token'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Record answer
     */
    public function recordAnswer(
        int  $attemptId,
        int  $questionId,
        ?int $selectedOptionId,
        bool $isCorrect
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO quiz_answers (attempt_id, question_id, selected_option_id, is_correct)
             VALUES (:attempt_id, :question_id, :option_id, :is_correct)
             ON DUPLICATE KEY UPDATE
                selected_option_id = VALUES(selected_option_id),
                is_correct = VALUES(is_correct)'
        );
        $stmt->execute([
            ':attempt_id' => $attemptId,
            ':question_id' => $questionId,
            ':option_id'   => $selectedOptionId,
            ':is_correct'  => (int) $isCorrect,
        ]);
    }

    /**
     * Update attempt score and status after MCQ
     */
    public function finalizeAttempt(int $attemptId, int $correctCount, int $total): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET status = "completed",
                 correct_answers = :correct,
                 total_questions = :total,
                 completed_at = NOW()
             WHERE id = :id AND voice_submitted = 0'
        );
        return $stmt->execute([
            ':correct' => $correctCount,
            ':total'   => $total,
            ':id'      => $attemptId,
        ]);
    }

    /**
     * Save voice file path and set status → voice_submitted
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

    // ================================================================
    // ADMIN REPORT & STATS
    // ================================================================

    /**
     * Admin dashboard stats card
     */
    public function getDashboardStats(int $quizId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(id)                                              AS total_participants,
                SUM(voice_submitted = 1)                               AS completed_count,
                SUM(voice_submitted = 0)                               AS abandoned_count,
                SUM(voice_submitted = 1 AND admin_notified = 0)        AS unreviewed_count,
                ROUND(
                    SUM(voice_submitted = 1) / NULLIF(COUNT(id), 0) * 100, 1
                )                                                      AS completion_pct,
                ROUND(
                    SUM(voice_submitted = 0) / NULLIF(COUNT(id), 0) * 100, 1
                )                                                      AS abandoned_pct
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
            'SELECT id, participant_name, gender, whatsapp_number, email,
                    status, total_questions, correct_answers, voice_submitted,
                    voice_file_path, voice_reviewed, voice_note, admin_notified,
                    started_at, completed_at
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id
             ORDER BY started_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':quiz_id', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Save voice review note and set admin_notified = 1
     */
    public function saveVoiceReviewNote(int $attemptId, string $note): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quiz_attempts
             SET voice_note = :note,
                 voice_reviewed = 1,
                 admin_notified = 1
             WHERE id = :id AND voice_submitted = 1'
        );
        return $stmt->execute([':note' => $note, ':id' => $attemptId]);
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

    // ================================================================
    // ADDITIONAL QUERY METHODS (Used in Service)
    // ================================================================

    /**
     * Find attempt by ID (internal use)
     */
    public function findAttemptById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, quiz_id, participant_name, gender, whatsapp_number, email,
                    status, total_questions, correct_answers,
                    voice_submitted, voice_file_path, session_token,
                    started_at, completed_at
             FROM quiz_attempts
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Number of correct/wrong answers for an attempt
     */
    public function getAnswerStats(int $attemptId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(id)           AS total_answered,
                SUM(is_correct = 1) AS correct_count,
                SUM(is_correct = 0) AS wrong_count
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
                COUNT(qa.id)           AS total,
                SUM(qa.is_correct = 1) AS correct
             FROM quiz_answers qa
             INNER JOIN quiz_questions qq ON qq.id = qa.question_id
             WHERE qa.attempt_id = :attempt_id
             GROUP BY qq.type'
        );
        $stmt->execute([':attempt_id' => $attemptId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Indexed by type for easy access in view
        $result = [];
        foreach ($rows as $row) {
            $result[$row['type']] = [
                'total'   => (int) $row['total'],
                'correct' => (int) $row['correct'],
            ];
        }
        return $result;
    }
}
