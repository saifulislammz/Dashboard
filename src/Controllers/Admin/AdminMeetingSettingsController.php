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

        $googleAccounts = $this->providerRepo->findAllByProvider('google_meet');
        $zoomAccounts   = $this->providerRepo->findAllByProvider('zoom');

        // Pass Google Redirect URI to view as a plain variable (views cannot call $this->...)
        $googleRedirectUri = $this->getGoogleRedirectUri();

        // Build Google Auth URLs per account for the view
        $googleAuthUrls = [];
        foreach ($googleAccounts as $account) {
            if (!empty($account['client_id'])) {
                $googleAuthUrls[$account['id']] = $this->getGoogleAuthUrl($account['client_id'], $account['id']);
            }
        }

        // Fetch global settings
        $stmt = $this->db->query("SELECT setting_key, setting_val FROM meeting_settings");
        $settingsDb = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        
        $settings = array_merge([
            'default_timezone'          => 'Asia/Dhaka',
            'join_open_minutes_before'  => '10',
            'reminder_enabled'          => '1',
            'reminder_minutes_before'   => '30',
            'default_provider'          => 'zoom',
        ], $settingsDb);

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');
                
                $action = $_POST['action'] ?? '';

                if ($action === 'add_google') {
                    $this->providerRepo->createAccount('google_meet', [
                        'nickname'      => trim($_POST['nickname'] ?? ''),
                        'client_id'     => trim($_POST['google_client_id'] ?? ''),
                        'client_secret' => trim($_POST['google_client_secret'] ?? ''),
                    ]);
                    header('Location: /admin/settings/meetings.php?success=' . urlencode('Google account slot added. Please connect it now.'));
                    exit;
                }
                elseif ($action === 'add_zoom') {
                    $accountId = $this->providerRepo->createAccount('zoom', [
                        'nickname'        => trim($_POST['nickname'] ?? ''),
                        'client_id'       => trim($_POST['zoom_client_id'] ?? ''),
                        'client_secret'   => trim($_POST['zoom_client_secret'] ?? ''),
                        'zoom_account_id' => trim($_POST['zoom_account_id'] ?? ''),
                    ]);
                    
                    // Immediately fetch token and verify connection
                    $account = $this->providerRepo->findById($accountId);
                    $zoomProvider = new \App\Integrations\Zoom\ZoomProvider($this->db, $account);
                    
                    if ($zoomProvider->refreshToken()) {
                        header('Location: /admin/settings/meetings.php?success=' . urlencode('Zoom credentials saved and connected successfully.'));
                    } else {
                        header('Location: /admin/settings/meetings.php?error=' . urlencode('Zoom credentials saved, but connection failed. Check your API details.'));
                    }
                    exit;
                }
                elseif ($action === 'update_google') {
                    $accountId = (int)($_POST['account_id'] ?? 0);
                    $this->providerRepo->updateAccountCredentials($accountId, [
                        'nickname'      => trim($_POST['nickname'] ?? ''),
                        'client_id'     => trim($_POST['google_client_id'] ?? ''),
                        'client_secret' => trim($_POST['google_client_secret'] ?? ''),
                    ]);
                    header('Location: /admin/settings/meetings.php?success=' . urlencode('Google account updated.'));
                    exit;
                }
                elseif ($action === 'update_zoom') {
                    $accountId = (int)($_POST['account_id'] ?? 0);
                    $this->providerRepo->updateAccountCredentials($accountId, [
                        'nickname'        => trim($_POST['nickname'] ?? ''),
                        'client_id'       => trim($_POST['zoom_client_id'] ?? ''),
                        'client_secret'   => trim($_POST['zoom_client_secret'] ?? ''),
                        'zoom_account_id' => trim($_POST['zoom_account_id'] ?? ''),
                    ]);
                    
                    $account = $this->providerRepo->findById($accountId);
                    $zoomProvider = new \App\Integrations\Zoom\ZoomProvider($this->db, $account);
                    
                    if ($zoomProvider->refreshToken()) {
                        header('Location: /admin/settings/meetings.php?success=' . urlencode('Zoom credentials updated and connected successfully.'));
                    } else {
                        header('Location: /admin/settings/meetings.php?error=' . urlencode('Zoom credentials updated, but connection failed. Check your API details.'));
                    }
                    exit;
                }
                elseif ($action === 'save_settings') {
                    $stmt = $this->db->prepare("
                        INSERT INTO meeting_settings (setting_key, setting_val) 
                        VALUES (:key, :val) 
                        ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)
                    ");
                    $keysToSave = [
                        'default_timezone', 'join_open_minutes_before', 'reminder_enabled',
                        'reminder_minutes_before', 'default_provider'
                    ];
                    foreach ($keysToSave as $k) {
                        $val = $_POST[$k] ?? '';
                        $stmt->execute(['key' => $k, 'val' => $val]);
                    }
                    header('Location: /admin/settings/meetings.php?success=' . urlencode('Global settings saved successfully.'));
                    exit;
                }
                elseif ($action === 'disconnect_account') {
                    $accountId = (int)($_POST['account_id'] ?? 0);
                    $this->providerRepo->disconnect($accountId);
                    header('Location: /admin/settings/meetings.php?success=' . urlencode('Account disconnected.'));
                    exit;
                }
                elseif ($action === 'delete_account') {
                    $accountId = (int)($_POST['account_id'] ?? 0);
                    $this->providerRepo->deleteAccount($accountId);
                    header('Location: /admin/settings/meetings.php?success=' . urlencode('Account deleted.'));
                    exit;
                }
            } catch (\Exception $e) {
                header('Location: /admin/settings/meetings.php?error=' . urlencode($e->getMessage()));
                exit;
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
        
        $code  = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        
        if (!$code) {
            header('Location: /admin/settings/meetings.php?error=Missing authorization code');
            exit;
        }
        
        // Extract account ID from state
        if (!preg_match('/^account_(\d+)$/', $state, $matches)) {
            header('Location: /admin/settings/meetings.php?error=Invalid state parameter');
            exit;
        }
        
        $accountId = (int)$matches[1];
        $account = $this->providerRepo->findById($accountId);

        if (!$account || empty($account['client_id']) || empty($account['client_secret']) || $account['provider'] !== 'google_meet') {
            header('Location: /admin/settings/meetings.php?error=Google credentials not configured or invalid account');
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
            accountId:     $accountId,
            accessToken:   $accessToken,
            refreshToken:  $json['refresh_token'] ?? null,
            expiresAt:     $expiresAt,
            accountEmail:  $email,
            providerAccountId:     $sub
        );
        $this->providerRepo->markConnectedBy($accountId, $auth->getUserId());

        header('Location: /admin/settings/meetings.php?success=Google account connected successfully');
        exit;
    }

    public function getGoogleRedirectUri(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        return "{$protocol}://{$host}/admin/settings/google_callback.php";
    }

    public function getGoogleAuthUrl(string $clientId, int $accountId): string
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
            'state'         => 'account_' . $accountId,
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
}
