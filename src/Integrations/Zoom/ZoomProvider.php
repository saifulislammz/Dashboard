<?php

declare(strict_types=1);

namespace App\Integrations\Zoom;

use App\Contracts\MeetingProviderInterface;
use App\DTOs\SessionDTO;
use App\DTOs\MeetingResultDTO;
use App\Repositories\ProviderAccountRepository;
use PDO;

/**
 * ZoomProvider
 *
 * Creates Zoom meetings via the Zoom REST API v2.
 * Uses Server-to-Server OAuth (recommended for institutes).
 *
 * App Setup in Zoom Marketplace:
 * 1. Go to https://marketplace.zoom.us → Develop → Build App → Server-to-Server OAuth
 * 2. Enable scopes: meeting:write:admin, meeting:read:admin
 * 3. Copy Account ID, Client ID, Client Secret → paste in settings page
 *
 * Server-to-Server OAuth works like this:
 * - No user login required
 * - We request a machine-to-machine access token using Client Credentials grant
 * - Token is valid for 1 hour (we cache it in provider_accounts)
 * - Every meeting is created under the institute's Zoom account
 */
class ZoomProvider implements MeetingProviderInterface
{
    private PDO    $db;
    private array  $account;
    private string $accessToken;
    private ProviderAccountRepository $providerRepo;

    private const API_BASE  = 'https://api.zoom.us/v2';
    private const TOKEN_URL = 'https://zoom.us/oauth/token';

    public function __construct(PDO $db, array $account)
    {
        $this->db           = $db;
        $this->account      = $account;
        $this->providerRepo = new ProviderAccountRepository($db);
        $this->accessToken  = $account['access_token'] ?? '';
    }

    // -------------------------------------------------------
    // CREATE MEETING
    // -------------------------------------------------------

    public function createMeeting(SessionDTO $session): MeetingResultDTO
    {
        if (!$this->isTokenValid()) {
            if (!$this->refreshToken()) {
                return MeetingResultDTO::failure('zoom', 'Zoom token refresh failed. Check credentials.');
            }
        }

        // Zoom expects ISO 8601 in UTC or with offset
        $startDt = new \DateTime(
            "{$session->sessionDate} {$session->startTime}",
            new \DateTimeZone($session->timezone)
        );

        $body = [
            'topic'      => $session->topic ?: "{$session->classroomName} - Live Session",
            'type'       => 2,       // 2 = Scheduled meeting
            'start_time' => $startDt->format('Y-m-d\TH:i:s'),
            'duration'   => $session->durationMinutes(),
            'timezone'   => $session->timezone,
            'agenda'     => $session->agenda ?: "Live class: {$session->classTitle}",
            'settings'   => [
                'host_video'         => true,
                'participant_video'  => true,
                'join_before_host'   => false,
                'mute_upon_entry'    => true,
                'waiting_room'       => true,
                'auto_recording'     => 'none', // 'cloud' for future recording support
            ],
        ];

        // Create meeting under the institute's user account
        // Using `me` requires the token has user-level scope; for admin scope use account email
        $url      = self::API_BASE . '/users/me/meetings';
        $response = $this->apiRequest('POST', $url, $body);

        if (!$response || isset($response['code'])) {
            $errMsg = $response['message'] ?? 'Unknown Zoom API error';
            return MeetingResultDTO::failure('zoom', $errMsg);
        }

        return new MeetingResultDTO(
            provider:          'zoom',
            providerMeetingId: (string) ($response['id'] ?? ''),
            joinUrl:           $response['join_url'] ?? '',
            meetLink:          null,          // Zoom uses join_url, not a meet link
            startUrl:          $response['start_url'] ?? null,
            passcode:          $response['password'] ?? null,
            rawResponse:       $response,
            success:           true,
        );
    }

    // -------------------------------------------------------
    // UPDATE MEETING
    // -------------------------------------------------------

    public function updateMeeting(string $providerMeetingId, SessionDTO $session): bool
    {
        if (!$this->isTokenValid()) {
            $this->refreshToken();
        }

        $startDt = new \DateTime(
            "{$session->sessionDate} {$session->startTime}",
            new \DateTimeZone($session->timezone)
        );

        $body = [
            'topic'      => $session->topic ?: "{$session->classroomName} - Live Session",
            'start_time' => $startDt->format('Y-m-d\TH:i:s'),
            'duration'   => $session->durationMinutes(),
            'timezone'   => $session->timezone,
            'agenda'     => $session->agenda ?: '',
        ];

        $url      = self::API_BASE . "/meetings/{$providerMeetingId}";
        $response = $this->apiRequest('PATCH', $url, $body);

        // Zoom returns 204 on success (empty body)
        return !isset($response['code']);
    }

    // -------------------------------------------------------
    // DELETE MEETING
    // -------------------------------------------------------

    public function deleteMeeting(string $providerMeetingId): bool
    {
        if (!$this->isTokenValid()) {
            $this->refreshToken();
        }

        $url      = self::API_BASE . "/meetings/{$providerMeetingId}";
        $response = $this->apiRequest('DELETE', $url);

        return !isset($response['code']);
    }

    // -------------------------------------------------------
    // TOKEN MANAGEMENT (Server-to-Server OAuth / Client Credentials)
    // -------------------------------------------------------

    public function isTokenValid(): bool
    {
        if (empty($this->accessToken)) {
            return false;
        }
        $expiresAt = (int) ($this->account['token_expires_at'] ?? 0);
        return $expiresAt > (time() + 60);
    }

    /**
     * Zoom Server-to-Server: fetch new access token via Client Credentials grant.
     * There is no refresh_token — we always request a new one.
     */
    public function refreshToken(): bool
    {
        $clientId     = $this->account['client_id'] ?? '';
        $clientSecret = $this->account['client_secret'] ?? '';
        $accountId    = $this->account['zoom_account_id'] ?? $this->account['account_id'] ?? '';

        if (!$clientId || !$clientSecret || !$accountId) {
            return false;
        }

        $url  = self::TOKEN_URL . '?grant_type=account_credentials&account_id=' . urlencode($accountId);
        $auth = base64_encode("{$clientId}:{$clientSecret}");

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($result, true);

        if (!isset($json['access_token'])) {
            return false;
        }

        $expiresAt = time() + ((int) ($json['expires_in'] ?? 3600));

        $this->providerRepo->saveTokens(
            provider:      'zoom',
            accessToken:   $json['access_token'],
            refreshToken:  null,                    // Zoom S2S has no refresh token
            expiresAt:     $expiresAt,
            accountEmail:  $this->account['account_email'] ?? '',
            accountId:     $accountId,
        );

        $this->accessToken               = $json['access_token'];
        $this->account['token_expires_at'] = $expiresAt;

        return true;
    }

    public function getProviderSlug(): string
    {
        return 'zoom';
    }

    // -------------------------------------------------------
    // Internal cURL helper
    // -------------------------------------------------------

    private function apiRequest(string $method, string $url, array $body = []): array
    {
        $ch      = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        } elseif ($method === 'PATCH') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $options[CURLOPT_POSTFIELDS]    = json_encode($body);
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            return [];
        }

        return json_decode($result, true) ?? [];
    }
}
