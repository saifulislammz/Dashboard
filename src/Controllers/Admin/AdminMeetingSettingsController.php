<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ProviderAccountRepository;
use PDO;

class AdminMeetingSettingsController
{
    private PDO $db;
    private ProviderAccountRepository $providerRepo;

    public function __construct(PDO $db, ProviderAccountRepository $providerRepo)
    {
        $this->db           = $db;
        $this->providerRepo = $providerRepo;
    }

    public function index(): void
    {
        global $auth;
        $pageTitle  = 'Meeting Integrations Settings';
        $activeMenu = 'classrooms_meetings';

        $googleAccount = $this->providerRepo->findByProvider('google_meet');
        $zoomAccount   = $this->providerRepo->findByProvider('zoom');

        // Fetch global settings
        $stmt = $this->db->query("SELECT setting_key, setting_val FROM meeting_settings");
        $settingsDb = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        
        $settings = array_merge([
            'default_timezone'          => 'Asia/Dhaka',
            'join_open_minutes_before'  => '10',
            'reminder_enabled'          => '1',
            'reminder_minutes_before'   => '30',
            'expose_direct_link'        => '0',
            'default_provider'          => 'zoom',
        ], $settingsDb);

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');
                
                $action = $_POST['action'] ?? '';

                if ($action === 'save_google') {
                    $this->providerRepo->upsertCredentials('google_meet', [
                        'client_id'     => trim($_POST['google_client_id'] ?? ''),
                        'client_secret' => trim($_POST['google_client_secret'] ?? ''),
                    ]);
                    $success = 'Google credentials saved successfully.';
                    $googleAccount = $this->providerRepo->findByProvider('google_meet'); // refresh
                }
                elseif ($action === 'save_zoom') {
                    $this->providerRepo->upsertCredentials('zoom', [
                        'client_id'       => trim($_POST['zoom_client_id'] ?? ''),
                        'client_secret'   => trim($_POST['zoom_client_secret'] ?? ''),
                        'zoom_account_id' => trim($_POST['zoom_account_id'] ?? ''),
                    ]);
                    $success = 'Zoom credentials saved successfully.';
                    $zoomAccount = $this->providerRepo->findByProvider('zoom'); // refresh
                }
                elseif ($action === 'save_settings') {
                    $stmt = $this->db->prepare("
                        INSERT INTO meeting_settings (setting_key, setting_val) 
                        VALUES (:key, :val) 
                        ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)
                    ");
                    $keysToSave = [
                        'default_timezone', 'join_open_minutes_before', 'reminder_enabled',
                        'reminder_minutes_before', 'expose_direct_link', 'default_provider'
                    ];
                    foreach ($keysToSave as $k) {
                        $val = $_POST[$k] ?? '';
                        $stmt->execute(['key' => $k, 'val' => $val]);
                        $settings[$k] = $val; // update local array
                    }
                    $success = 'Global settings saved successfully.';
                }
                elseif ($action === 'disconnect_google') {
                    $this->providerRepo->disconnect('google_meet');
                    $success = 'Google account disconnected.';
                    $googleAccount = $this->providerRepo->findByProvider('google_meet');
                }
                elseif ($action === 'disconnect_zoom') {
                    $this->providerRepo->disconnect('zoom');
                    $success = 'Zoom account disconnected.';
                    $zoomAccount = $this->providerRepo->findByProvider('zoom');
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        require __DIR__ . '/../../../views/admin/settings/meetings.php';
    }

    /**
     * Handles the OAuth callback from Google
     */
    public function handleGoogleCallback(): void
    {
        global $auth;
        
        $code = $_GET['code'] ?? '';
        if (!$code) {
            header('Location: /admin/settings/meetings.php?error=Missing authorization code');
            exit;
        }

        $account = $this->providerRepo->findByProvider('google_meet');
        if (!$account || empty($account['client_id']) || empty($account['client_secret'])) {
            header('Location: /admin/settings/meetings.php?error=Google credentials not configured');
            exit;
        }

        $redirectUri = $this->getGoogleRedirectUri();

        // Exchange code for token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => $account['client_id'],
                'client_secret' => $account['client_secret'],
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['error'])) {
            $msg = urlencode($json['error_description'] ?? $json['error']);
            header("Location: /admin/settings/meetings.php?error={$msg}");
            exit;
        }

        // Fetch user email to store who connected it
        $accessToken = $json['access_token'];
        $ch2 = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        ]);
        $userInfoRaw = curl_exec($ch2);
        curl_close($ch2);
        
        $userInfo = json_decode($userInfoRaw, true);
        $email    = $userInfo['email'] ?? 'unknown@google.com';
        $sub      = $userInfo['id'] ?? '';

        $expiresAt = time() + ((int)($json['expires_in'] ?? 3600));

        $this->providerRepo->saveTokens(
            provider:      'google_meet',
            accessToken:   $accessToken,
            refreshToken:  $json['refresh_token'] ?? null,
            expiresAt:     $expiresAt,
            accountEmail:  $email,
            accountId:     $sub
        );
        $this->providerRepo->markConnectedBy('google_meet', $auth->getUserId());

        header('Location: /admin/settings/meetings.php?success=Google account connected successfully');
        exit;
    }

    public function getGoogleRedirectUri(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        return "{$protocol}://{$host}/admin/settings/google_callback.php";
    }

    public function getGoogleAuthUrl(string $clientId): string
    {
        $redirectUri = $this->getGoogleRedirectUri();
        $scopes = [
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/userinfo.email',
        ];
        
        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => implode(' ', $scopes),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
}
