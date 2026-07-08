<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * SessionDTO
 *
 * Immutable data transfer object that carries all session
 * information needed to create a meeting on any provider.
 * Decouples the DB layer from the provider integration layer.
 */
final class SessionDTO
{
    public function __construct(
        public readonly int    $sessionId,
        public readonly int    $classroomId,
        public readonly string $classroomName,
        public readonly string $classTitle,
        public readonly string $topic,
        public readonly string $agenda,
        public readonly string $sessionDate,     // Y-m-d
        public readonly string $startTime,       // H:i:s
        public readonly string $endTime,         // H:i:s
        public readonly string $timezone,        // e.g. Asia/Dhaka
        public readonly string $provider,        // google_meet | zoom
        public readonly ?string $teacherEmail,
        public readonly ?string $teacherName,
        public readonly ?string $studentEmail = null,
        public readonly ?string $studentName = null,
    ) {
        try {
            new \DateTimeZone($this->timezone);
            new \DateTime("{$this->sessionDate} {$this->startTime}");
            new \DateTime("{$this->sessionDate} {$this->endTime}");
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid timezone or date/time format in SessionDTO: " . $e->getMessage());
        }
    }

    /**
     * Returns ISO 8601 datetime string for the start time.
     * Used by Google Calendar API.
     */
    public function startDateTimeISO(): string
    {
        $dt = new \DateTime("{$this->sessionDate} {$this->startTime}", new \DateTimeZone($this->timezone));
        return $dt->format(\DateTime::ATOM);
    }

    /**
     * Returns ISO 8601 datetime string for the end time.
     */
    public function endDateTimeISO(): string
    {
        $dt = new \DateTime("{$this->sessionDate} {$this->endTime}", new \DateTimeZone($this->timezone));
        return $dt->format(\DateTime::ATOM);
    }

    /**
     * Duration in minutes (used by Zoom API)
     */
    public function durationMinutes(): int
    {
        $start = new \DateTime("{$this->sessionDate} {$this->startTime}");
        $end   = new \DateTime("{$this->sessionDate} {$this->endTime}");
        return (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);
    }
}
