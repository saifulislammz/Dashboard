<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * ProviderAccountRepository
 *
 * Manages provider OAuth tokens & connection status.
 * Tokens are stored encrypted (AES-256-CBC via openssl).
 */
class ProviderAccountRepository
{
    private PDO    $db;
    private string $encryptionKey;

    public function __construct(PDO $db)
    {
        $this->db            = $db;
        $key = $_ENV['APP_ENCRYPTION_KEY'] ?? '';
        if (empty($key)) {
            // For security, never default to a weak key in a real application.
            throw new \RuntimeException('Critical Error: APP_ENCRYPTION_KEY is not set in .env');
        }
        $this->encryptionKey = $key;
    }

    // -------------------------------------------------------
    // READ
    // -------------------------------------------------------

    public function findByProvider(string $provider): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM provider_accounts WHERE provider = :provider LIMIT 1"
        );
        $stmt->execute(['provider' => $provider]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Decrypt sensitive fields
        $row['access_token']   = $this->decrypt($row['access_token'] ?? '');
        $row['refresh_token']  = $this->decrypt($row['refresh_token'] ?? '');
        $row['client_id']      = $this->decrypt($row['client_id'] ?? '');
        $row['client_secret']  = $this->decrypt($row['client_secret'] ?? '');

        return $row;
    }

    public function getConnectedProviders(): array
    {
        $stmt = $this->db->query(
            "SELECT provider, account_email, is_connected, connected_at
             FROM provider_accounts WHERE is_connected = 1"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT id, provider, account_email, is_connected, connected_at,
                    last_token_refresh, token_expires_at
             FROM provider_accounts ORDER BY provider"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------
    // WRITE
    // -------------------------------------------------------

    public function upsertCredentials(string $provider, array $data): bool
    {
        $encrypt = fn($v) => $v ? $this->encrypt($v) : null;

        $stmt = $this->db->prepare("
            INSERT INTO provider_accounts
                (provider, client_id, client_secret, zoom_account_id, updated_at)
            VALUES
                (:provider, :client_id, :client_secret, :zoom_account_id, NOW())
            ON DUPLICATE KEY UPDATE
                client_id      = VALUES(client_id),
                client_secret  = VALUES(client_secret),
                zoom_account_id = VALUES(zoom_account_id),
                updated_at     = NOW()
        ");

        return $stmt->execute([
            'provider'       => $provider,
            'client_id'      => $encrypt($data['client_id'] ?? null),
            'client_secret'  => $encrypt($data['client_secret'] ?? null),
            'zoom_account_id'=> $data['zoom_account_id'] ?? null,
        ]);
    }

    public function saveTokens(
        string  $provider,
        string  $accessToken,
        ?string $refreshToken,
        int     $expiresAt,
        string  $accountEmail,
        ?string $accountId = null
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE provider_accounts
            SET access_token      = :access_token,
                refresh_token     = :refresh_token,
                token_expires_at  = :expires_at,
                account_email     = :account_email,
                account_id        = :account_id,
                is_connected      = 1,
                connected_at      = NOW(),
                last_token_refresh= NOW()
            WHERE provider = :provider
        ");

        return $stmt->execute([
            'access_token'  => $this->encrypt($accessToken),
            'refresh_token' => $refreshToken ? $this->encrypt($refreshToken) : null,
            'expires_at'    => $expiresAt,
            'account_email' => $accountEmail,
            'account_id'    => $accountId,
            'provider'      => $provider,
        ]);
    }

    public function disconnect(string $provider): bool
    {
        $stmt = $this->db->prepare("
            UPDATE provider_accounts
            SET access_token = NULL, refresh_token = NULL,
                is_connected = 0, account_email = NULL,
                token_expires_at = NULL
            WHERE provider = :provider
        ");
        return $stmt->execute(['provider' => $provider]);
    }

    public function markConnectedBy(string $provider, int $adminId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE provider_accounts SET connected_by = :admin_id WHERE provider = :provider"
        );
        return $stmt->execute(['admin_id' => $adminId, 'provider' => $provider]);
    }

    // -------------------------------------------------------
    // Encryption helpers (AES-256-CBC)
    // -------------------------------------------------------

    private function encrypt(string $plaintext): string
    {
        $iv         = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $ciphertext);
    }

    private function decrypt(string $encoded): string
    {
        if (empty($encoded)) {
            return '';
        }
        $data       = base64_decode($encoded);
        $iv         = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return (string) openssl_decrypt($ciphertext, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
    }
}
