<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * MeetingJobRepository
 *
 * Manages meeting_generation_jobs and meeting_generation_job_items.
 * Tracks bulk session generation progress.
 */
class MeetingJobRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // -------------------------------------------------------
    // JOBS
    // -------------------------------------------------------

    public function createJob(int $classroomId, string $provider, int $totalSessions, int $createdBy): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO meeting_generation_jobs
                (classroom_id, provider, total_sessions, status, created_by)
            VALUES
                (:classroom_id, :provider, :total, 'queued', :created_by)
        ");
        $stmt->execute([
            'classroom_id' => $classroomId,
            'provider'     => $provider,
            'total'        => $totalSessions,
            'created_by'   => $createdBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markJobStarted(int $jobId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE meeting_generation_jobs SET status = 'processing', started_at = NOW() WHERE id = :id"
        );
        return $stmt->execute(['id' => $jobId]);
    }

    public function updateJobProgress(int $jobId, int $processed, int $succeeded, int $failed): bool
    {
        $stmt = $this->db->prepare("
            UPDATE meeting_generation_jobs
            SET processed = :processed, succeeded = :succeeded, failed = :failed
            WHERE id = :id
        ");
        return $stmt->execute([
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed'    => $failed,
            'id'        => $jobId,
        ]);
    }

    public function markJobCompleted(int $jobId, int $failed): bool
    {
        $finalStatus = $failed > 0 ? 'partial' : 'completed';
        $stmt        = $this->db->prepare("
            UPDATE meeting_generation_jobs
            SET status = :status, completed_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['status' => $finalStatus, 'id' => $jobId]);
    }

    public function findJobById(int $jobId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, classroom_id, provider, total_sessions, processed, succeeded, failed, status, created_by, started_at, completed_at FROM meeting_generation_jobs WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getRecentJobsByClassroom(int $classroomId, int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT id, provider, total_sessions, processed, succeeded, failed,
                   status, created_at, completed_at
            FROM meeting_generation_jobs
            WHERE classroom_id = :classroom_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':classroom_id', $classroomId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $limit,       PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------
    // JOB ITEMS
    // -------------------------------------------------------

    /**
     * Bulk insert job items (one per session)
     */
    public function bulkCreateItems(int $jobId, array $sessionIds): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO meeting_generation_job_items (job_id, session_id, status)
            VALUES (:job_id, :session_id, 'pending')
        ");
        foreach ($sessionIds as $sid) {
            $stmt->execute(['job_id' => $jobId, 'session_id' => $sid]);
        }
        return true;
    }

    public function markItemSuccess(int $jobId, int $sessionId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE meeting_generation_job_items
            SET status = 'success', attempts = attempts + 1, processed_at = NOW()
            WHERE job_id = :job_id AND session_id = :session_id
        ");
        return $stmt->execute(['job_id' => $jobId, 'session_id' => $sessionId]);
    }

    public function markItemFailed(int $jobId, int $sessionId, string $error): bool
    {
        $stmt = $this->db->prepare("
            UPDATE meeting_generation_job_items
            SET status = 'failed',
                attempts = attempts + 1,
                last_error = :error,
                processed_at = NOW()
            WHERE job_id = :job_id AND session_id = :session_id
        ");
        return $stmt->execute([
            'error'      => mb_substr($error, 0, 511),
            'job_id'     => $jobId,
            'session_id' => $sessionId,
        ]);
    }

    public function getPendingItems(int $jobId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, job_id, session_id, status, attempts, last_error, processed_at FROM meeting_generation_job_items
             WHERE job_id = :job_id AND status = 'pending'
             ORDER BY id ASC"
        );
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
