<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * ScheduleConflictException
 *
 * Thrown when a session cannot be created because the teacher or student
 * already has an overlapping session in another classroom on the same date/time.
 *
 * Carries structured metadata so the controller and view can render
 * a precise, user-friendly conflict modal.
 */
class ScheduleConflictException extends RuntimeException
{
    private string  $conflictType;  // 'teacher' | 'student'
    private string  $personName;    // The conflicting person's name
    private string  $sessionDate;
    private string  $startTime;
    private string  $endTime;
    private string  $conflictClassName; // The other classroom name

    public function __construct(
        string $conflictType,
        string $personName,
        string $sessionDate,
        string $startTime,
        string $endTime,
        string $conflictClassName,
        string $message = '',
        int    $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->conflictType      = $conflictType;
        $this->personName        = $personName;
        $this->sessionDate       = $sessionDate;
        $this->startTime         = $startTime;
        $this->endTime           = $endTime;
        $this->conflictClassName = $conflictClassName;

        if ($message === '') {
            $label   = $conflictType === 'teacher' ? 'Teacher' : 'Student';
            $message = "{$label} '{$personName}' already has a session in '{$conflictClassName}' on {$sessionDate} between {$startTime} and {$endTime}.";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getConflictType(): string  { return $this->conflictType; }
    public function getPersonName(): string    { return $this->personName; }
    public function getSessionDate(): string   { return $this->sessionDate; }
    public function getStartTime(): string     { return $this->startTime; }
    public function getEndTime(): string       { return $this->endTime; }
    public function getConflictClassName(): string { return $this->conflictClassName; }
}
