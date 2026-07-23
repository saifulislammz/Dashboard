<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * TimeSlotConflictException
 *
 * Thrown when a session cannot be created because the requested
 * date/time window has no available provider account (all accounts
 * are already assigned to an overlapping class session).
 *
 * Carries structured metadata so the controller and view can render
 * a precise, user-friendly conflict modal without exposing internals.
 */
class TimeSlotConflictException extends RuntimeException
{
    private string $sessionDate;
    private string $startTime;
    private string $endTime;
    private string $provider;

    public function __construct(
        string $provider,
        string $sessionDate,
        string $startTime,
        string $endTime,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->provider    = $provider;
        $this->sessionDate = $sessionDate;
        $this->startTime   = $startTime;
        $this->endTime     = $endTime;

        if ($message === '') {
            $message = "Time slot conflict: no available {$provider} account for {$sessionDate} {$startTime}–{$endTime}.";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getSessionDate(): string
    {
        return $this->sessionDate;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
