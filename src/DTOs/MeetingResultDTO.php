<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * MeetingResultDTO
 *
 * Immutable response object returned by any provider
 * after a meeting is successfully created.
 * Normalises the different response structures from
 * Google Meet and Zoom into one common format.
 */
final class MeetingResultDTO
{
    public function __construct(
        /** Provider slug: google_meet | zoom */
        public readonly string  $provider,

        /** Provider-assigned meeting ID (Zoom numeric ID or Google event ID) */
        public readonly string  $providerMeetingId,

        /** Participant join URL */
        public readonly string  $joinUrl,

        /** Google Meet link (meet.google.com/xxx) — null for Zoom */
        public readonly ?string $meetLink,

        /** Host start URL — Zoom only, null for Google */
        public readonly ?string $startUrl,

        /** Zoom passcode — null if not set or Google */
        public readonly ?string $passcode,

        /** Full raw API response array — stored as JSON in DB for future use */
        public readonly array   $rawResponse,

        /** Whether creation was successful */
        public readonly bool    $success,

        /** Error message if success = false */
        public readonly ?string $errorMessage = null,
    ) {}

    /**
     * Create a failure result — used when provider API call fails.
     */
    public static function failure(string $provider, string $errorMessage): self
    {
        return new self(
            provider:         $provider,
            providerMeetingId: '',
            joinUrl:          '',
            meetLink:         null,
            startUrl:         null,
            passcode:         null,
            rawResponse:      [],
            success:          false,
            errorMessage:     $errorMessage,
        );
    }
}
