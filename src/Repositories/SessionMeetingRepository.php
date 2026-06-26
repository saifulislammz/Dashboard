<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * SessionMeetingRepository
 *
 * Manages the session_meetings table.
 * One meeting record per session (1:1 with class_sessions).
 */
class SessionMeetingRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findBySessionId(int $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM session_meetings WHERE session_id = :session_id LIMIT 1"
        );
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create a pending meeting record (before provider API call)
     */
    public function createPending(int $sessionId, string $provider): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO session_meetings (session_id, provider, generation_status)
            VALUES (:session_id, :provider, 'pending')
            ON DUPLICATE KEY UPDATE
                generation_status = 'pending',
                updated_at = NOW()
        ");
        $stmt->execute(['session_id' => $sessionId, 'provider' => $provider]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Save successful meeting creation result
     */
    public function saveSuccess(int $sessionId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE session_meetings
            SET provider_meeting_id  = :provider_meeting_id,
                provider_event_id    = :provider_event_id,
                join_url             = :join_url,
                start_url            = :start_url,
                meet_link            = :meet_link,
                passcode             = :passcode,
                generation_status    = 'success',
                error_message        = NULL,
                provider_response_raw = :raw_response,
                generation_attempts  = generation_attempts + 1,
                last_attempt_at      = NOW()
            WHERE session_id = :session_id
        ");

        return $stmt->execute([
            'provider_meeting_id' => $data['provider_meeting_id'],
            'provider_event_id'   => $data['provider_event_id'] ?? null,
            'join_url'            => $data['join_url'],
            'start_url'           => $data['start_url'] ?? null,
            'meet_link'           => $data['meet_link'] ?? null,
            'passcode'            => $data['passcode'] ?? null,
            'raw_response'        => json_encode($data['raw_response'] ?? []),
            'session_id'          => $sessionId,
        ]);
    }

    /**
     * Mark a meeting generation as failed
     */
    public function saveFailed(int $sessionId, string $errorMessage): bool
    {
        $stmt = $this->db->prepare("
            UPDATE session_meetings
            SET generation_status   = 'failed',
                error_message       = :error,
                generation_attempts = generation_attempts + 1,
                last_attempt_at     = NOW()
            WHERE session_id = :session_id
        ");
        return $stmt->execute([
            'error'      => mb_substr($errorMessage, 0, 511),
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Reset a failed meeting back to pending (for retry)
     */
    public function resetForRetry(int $sessionId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE session_meetings
            SET generation_status = 'pending', error_message = NULL
            WHERE session_id = :session_id
              AND generation_status = 'failed'
        ");
        return $stmt->execute(['session_id' => $sessionId]);
    }

    /**
     * Get all failed meetings (for admin retry list)
     */
    public function getFailedMeetings(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT sm.session_id, sm.error_message, sm.generation_attempts,
                   sm.last_attempt_at,
                   cs.session_date, cs.start_time, cs.topic, cs.provider
            FROM session_meetings sm
            JOIN class_sessions cs ON cs.id = sm.session_id
            WHERE cs.classroom_id = :classroom_id
              AND sm.generation_status = 'failed'
            ORDER BY cs.session_date ASC
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markCancelled(int $sessionId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE session_meetings SET generation_status = 'cancelled' WHERE session_id = :session_id"
        );
        return $stmt->execute(['session_id' => $sessionId]);
    }
}
