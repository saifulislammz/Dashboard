<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\SessionDTO;
use App\DTOs\MeetingResultDTO;

/**
 * MeetingProviderInterface
 *
 * All meeting providers (Google Meet, Zoom, Teams etc.)
 * must implement this interface. This ensures the core
 * MeetingService is provider-agnostic.
 */
interface MeetingProviderInterface
{
    /**
     * Create a new meeting for a session.
     * Returns a MeetingResultDTO with join_url, provider_meeting_id etc.
     */
    public function createMeeting(SessionDTO $session): MeetingResultDTO;

    /**
     * Update an existing meeting (reschedule, rename topic etc.)
     */
    public function updateMeeting(string $providerMeetingId, SessionDTO $session): bool;

    /**
     * Delete / cancel a meeting on the provider side.
     */
    public function deleteMeeting(string $providerMeetingId): bool;

    /**
     * Check if the stored access token is still valid.
     */
    public function isTokenValid(): bool;

    /**
     * Refresh the access token using the stored refresh token.
     * Saves the new token back to the provider_accounts table.
     */
    public function refreshToken(): bool;

    /**
     * Return the provider slug: 'google_meet' or 'zoom'
     */
    public function getProviderSlug(): string;
}
