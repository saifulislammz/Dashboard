<?php

declare(strict_types=1);

namespace App\Integrations\Google;

use App\Contracts\MeetingProviderInterface;
use App\DTOs\SessionDTO;
use App\DTOs\MeetingResultDTO;
use App\Repositories\ProviderAccountRepository;
use App\Utils\Logger;
use PDO;

/**
 * GoogleMeetProvider
 *
 * Creates Google Meet links via the Google Calendar API.
 *
 * How it works:
 * 1. Admin connects a Google account via OAuth 2.0 from the settings page.
 * 2. The system stores access_token + refresh_token in provider_accounts (encrypted).
 * 3. When a session is created, we call Calendar API to create an event
 *    with conferenceData (requestId) → Google generates a Meet link.
 * 4. We extract hangoutLink / conferenceData.entryPoints from the response.
 * 5. Each session = one Calendar event = one Meet link.
 *
 * Required:
 * - Google Calendar API enabled in Cloud Console
 * - OAuth 2.0 credentials (Web Application type)
 * - Scopes: https://www.googleapis.com/auth/calendar.events
 *
 * Note: google/apiclient (composer package) is used if available.
 * If not installed, falls back to raw cURL. We use raw cURL for
 * maximum compatibility with shared hosting (no google/apiclient required).
 */
class GoogleMeetProvider implements MeetingProviderInterface
{
    private PDO    $db;
    private array  $account;
    private string $accessToken;
    private ProviderAccountRepository $providerRepo;
    private Logger $logger;

    private const CALENDAR_API_BASE = 'https://www.googleapis.com/calendar/v3';
    private const TOKEN_URL         = 'https://oauth2.googleapis.com/token';
    private const CALENDAR_ID       = 'primary'; // Use institute's primary calendar

    public function __construct(PDO $db, array $account)
    {
        $this->db           = $db;
        $this->account      = $account;
        $this->providerRepo = new ProviderAccountRepository($db);
        $this->accessToken  = $account['access_token'] ?? '';
        $this->logger       = new Logger();
    }

    // -------------------------------------------------------
    // CREATE MEETING (Core method)
    // -------------------------------------------------------

    public function createMeeting(SessionDTO $session): MeetingResultDTO
    {
        // Ensure token is valid, refresh if needed
        if (!$this->isTokenValid()) {
            if (!$this->refreshToken()) {
                $this->logger->error('Google token refresh failed', ['account_email' => $this->account['account_email'] ?? null]);
                return MeetingResultDTO::failure('google_meet', 'Google token expired and refresh failed.');
            }
        }

        // Build the Calendar event body with conferenceData
        $requestId = 'session-' . $session->sessionId . '-' . time(); // Idempotency key
        $body      = [
            'summary'         => $session->topic ?: "{$session->classroomName} - Live Session",
            'description'     => $session->agenda ?: "Live class session for {$session->classTitle}",
            'start'           => [
                'dateTime' => $session->startDateTimeISO(),
                'timeZone' => $session->timezone,
            ],
            'end'             => [
                'dateTime' => $session->endDateTimeISO(),
                'timeZone' => $session->timezone,
            ],
            'attendees'       => array_filter([
                $session->teacherEmail ? ['email' => $session->teacherEmail] : null,
                $session->studentEmail ? ['email' => $session->studentEmail] : null,
            ]),
            'conferenceData'  => [
                'createRequest' => [
                    'requestId'             => $requestId,
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ];

        // POST to Google Calendar API with conferenceDataVersion=1
        $url      = self::CALENDAR_API_BASE . '/calendars/' . self::CALENDAR_ID . '/events?conferenceDataVersion=1';
        $response = $this->apiRequest('POST', $url, $body);

        if (!$response || isset($response['error'])) {
            $errMsg = $response['error']['message'] ?? 'Unknown Google API error';
            $this->logger->error('Google createMeeting failed', [
                'session_id' => $session->sessionId,
                'error' => $errMsg,
                'response' => $response
            ]);
            return MeetingResultDTO::failure('google_meet', $errMsg);
        }

        // Extract Meet link from response
        $meetLink = $response['hangoutLink'] ?? null;

        // Also check conferenceData entryPoints for meet URL
        if (!$meetLink) {
            $entryPoints = $response['conferenceData']['entryPoints'] ?? [];
            foreach ($entryPoints as $ep) {
                if (($ep['entryPointType'] ?? '') === 'video') {
                    $meetLink = $ep['uri'];
                    break;
                }
            }
        }

        if (!$meetLink) {
            return MeetingResultDTO::failure('google_meet', 'Google Meet link not returned in API response.');
        }

        return new MeetingResultDTO(
            provider:          'google_meet',
            providerMeetingId: $response['id'],       // Google Calendar event ID
            joinUrl:           $meetLink,              // Meet link IS the join URL
            meetLink:          $meetLink,
            startUrl:          null,                   // Google has no separate host URL
            passcode:          null,                   // Google Meet has no passcode
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

        $body = [
            'summary' => $session->topic ?: "{$session->classroomName} - Live Session",
            'start'   => [
                'dateTime' => $session->startDateTimeISO(),
                'timeZone' => $session->timezone,
            ],
            'end'     => [
                'dateTime' => $session->endDateTimeISO(),
                'timeZone' => $session->timezone,
            ],
        ];

        $url      = self::CALENDAR_API_BASE . '/calendars/' . self::CALENDAR_ID . "/events/{$providerMeetingId}";
        $response = $this->apiRequest('PATCH', $url, $body);

        if (isset($response['error'])) {
            $this->logger->error('Google updateMeeting failed', [
                'meeting_id' => $providerMeetingId,
                'response' => $response
            ]);
            return false;
        }

        return true;
    }

    // -------------------------------------------------------
    // DELETE MEETING
    // -------------------------------------------------------

    public function deleteMeeting(string $providerMeetingId): bool
    {
        if (!$this->isTokenValid()) {
            $this->refreshToken();
        }

        $url      = self::CALENDAR_API_BASE . '/calendars/' . self::CALENDAR_ID . "/events/{$providerMeetingId}";
        $response = $this->apiRequest('DELETE', $url);

        if (isset($response['error'])) {
            $this->logger->error('Google deleteMeeting failed', [
                'meeting_id' => $providerMeetingId,
                'response' => $response
            ]);
            return false;
        }

        // DELETE returns 204 No Content on success (empty body)
        return $response === [] || $response === null;
    }

    // -------------------------------------------------------
    // TOKEN MANAGEMENT
    // -------------------------------------------------------

    public function isTokenValid(): bool
    {
        if (empty($this->accessToken)) {
            return false;
        }
        $expiresAt = (int) ($this->account['token_expires_at'] ?? 0);
        // Consider expired 60 seconds early for safety
        return $expiresAt > (time() + 60);
    }

    public function refreshToken(): bool
    {
        $refreshToken = $this->account['refresh_token'] ?? '';
        $clientId     = $this->account['client_id'] ?? '';
        $clientSecret = $this->account['client_secret'] ?? '';

        if (!$refreshToken || !$clientId || !$clientSecret) {
            return false;
        }

        $data = http_build_query([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($result, true);

        if (!isset($json['access_token'])) {
            $this->logger->error('Google token refresh failed - no access token in response', ['response' => $json]);
            return false;
        }

        $expiresAt = time() + ((int) ($json['expires_in'] ?? 3600));

        $this->providerRepo->saveTokens(
            accountId:     (int) $this->account['id'],
            accessToken:   $json['access_token'],
            refreshToken:  $json['refresh_token'] ?? $refreshToken,
            expiresAt:     $expiresAt,
            accountEmail:  $this->account['account_email'],
        );

        $this->accessToken               = $json['access_token'];
        $this->account['token_expires_at'] = $expiresAt;

        return true;
    }

    public function getProviderSlug(): string
    {
        return 'google_meet';
    }

    // -------------------------------------------------------
    // Internal cURL helper
    // -------------------------------------------------------

    private function apiRequest(string $method, string $url, array $body = []): array
    {
        $ch = curl_init($url);
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
        
        if (curl_errno($ch)) {
            $this->logger->error('Google cURL error', ['error' => curl_error($ch), 'url' => $url]);
        }
        
        curl_close($ch);

        if (!$result) {
            return [];
        }

        return json_decode($result, true) ?? [];
    }
}
