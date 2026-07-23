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
        $this->db = $db;
        // $_ENV is not always populated in Docker/VPS — fallback to getenv()
        $key = $_ENV['APP_ENCRYPTION_KEY'] ?? getenv('APP_ENCRYPTION_KEY') ?: '';
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
        // For backward compatibility, return the first one or default
        $stmt = $this->db->prepare(
            "SELECT id, provider, nickname, display_order, client_id, client_secret, zoom_account_id, access_token, refresh_token, token_expires_at, account_email, account_id, is_connected, connected_at, last_token_refresh, connected_by FROM provider_accounts WHERE provider = :provider ORDER BY display_order ASC, id ASC LIMIT 1"
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

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, provider, nickname, display_order, client_id, client_secret, zoom_account_id, access_token, refresh_token, token_expires_at, account_email, account_id, is_connected, connected_at, last_token_refresh, connected_by FROM provider_accounts WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
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

    public function findAllByProvider(string $provider): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, provider, nickname, display_order, client_id, client_secret, zoom_account_id, access_token, refresh_token, token_expires_at, account_email, account_id, is_connected, connected_at, last_token_refresh, connected_by 
             FROM provider_accounts 
             WHERE provider = :provider 
             ORDER BY display_order ASC, id ASC"
        );
        $stmt->execute(['provider' => $provider]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['access_token']   = $this->decrypt($row['access_token'] ?? '');
            $row['refresh_token']  = $this->decrypt($row['refresh_token'] ?? '');
            $row['client_id']      = $this->decrypt($row['client_id'] ?? '');
            $row['client_secret']  = $this->decrypt($row['client_secret'] ?? '');
        }

        return $rows;
    }

    public function findAvailableAccount(string $provider, string $date, string $startTime, string $endTime): ?array
    {
        // Anti-join via LEFT JOIN: avoids duplicate named parameters in subquery
        // (PDO native prepared statements forbid the same placeholder twice).
        // One binding for :provider, index-friendly: ref on provider + account_id.
        $stmt = $this->db->prepare("
            SELECT pa.id
            FROM provider_accounts pa
            LEFT JOIN class_sessions cs
                   ON cs.provider_account_id = pa.id
                  AND cs.provider    = :provider
                  AND cs.session_date = :date
                  AND cs.status NOT IN ('cancelled', 'failed')
                  AND cs.start_time  < :end_time
                  AND cs.end_time    > :start_time
            WHERE pa.provider    = :provider
              AND pa.is_connected = 1
              AND cs.id          IS NULL
            ORDER BY pa.display_order ASC, pa.id ASC
            LIMIT 1
        ");

        $stmt->execute([
            'provider'   => $provider,
            'date'       => $date,
            'start_time' => $startTime,
            'end_time'   => $endTime,
        ]);

        $accountId = $stmt->fetchColumn();

        return $accountId ? $this->findById((int) $accountId) : null;
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
            "SELECT id, provider, nickname, display_order, account_email, is_connected, connected_at,
                    last_token_refresh, token_expires_at
             FROM provider_accounts ORDER BY provider, display_order ASC, id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------
    // WRITE
    // -------------------------------------------------------

    public function createAccount(string $provider, array $data): int
    {
        $encrypt = fn($v) => $v ? $this->encrypt($v) : null;

        $stmt = $this->db->prepare("
            INSERT INTO provider_accounts
                (provider, nickname, display_order, client_id, client_secret, zoom_account_id, updated_at)
            VALUES
                (:provider, :nickname, :display_order, :client_id, :client_secret, :zoom_account_id, NOW())
        ");

        $stmt->execute([
            'provider'       => $provider,
            'nickname'       => $data['nickname'] ?? null,
            'display_order'  => (int) ($data['display_order'] ?? 0),
            'client_id'      => $encrypt($data['client_id'] ?? null),
            'client_secret'  => $encrypt($data['client_secret'] ?? null),
            'zoom_account_id'=> $data['zoom_account_id'] ?? null,
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function updateAccountCredentials(int $id, array $data): bool
    {
        $encrypt = fn($v) => $v ? $this->encrypt($v) : null;

        $stmt = $this->db->prepare("
            UPDATE provider_accounts
            SET nickname       = :nickname,
                display_order  = :display_order,
                client_id      = :client_id,
                client_secret  = :client_secret,
                zoom_account_id= :zoom_account_id,
                updated_at     = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            'id'             => $id,
            'nickname'       => $data['nickname'] ?? null,
            'display_order'  => (int) ($data['display_order'] ?? 0),
            'client_id'      => $encrypt($data['client_id'] ?? null),
            'client_secret'  => $encrypt($data['client_secret'] ?? null),
            'zoom_account_id'=> $data['zoom_account_id'] ?? null,
        ]);
    }

    public function saveTokens(
        int     $accountId,
        string  $accessToken,
        ?string $refreshToken,
        int     $expiresAt,
        string  $accountEmail,
        ?string $providerAccountId = null
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
            WHERE id = :id
        ");

        return $stmt->execute([
            'access_token'  => $this->encrypt($accessToken),
            'refresh_token' => $refreshToken ? $this->encrypt($refreshToken) : null,
            'expires_at'    => $expiresAt,
            'account_email' => $accountEmail,
            'account_id'    => $providerAccountId,
            'id'            => $accountId,
        ]);
    }

    public function disconnect(int $accountId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE provider_accounts
            SET access_token = NULL, refresh_token = NULL,
                is_connected = 0, account_email = NULL,
                token_expires_at = NULL
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $accountId]);
    }

    public function deleteAccount(int $accountId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM provider_accounts WHERE id = :id");
        return $stmt->execute(['id' => $accountId]);
    }

    public function markConnectedBy(int $accountId, int $adminId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE provider_accounts SET connected_by = :admin_id WHERE id = :id"
        );
        return $stmt->execute(['admin_id' => $adminId, 'id' => $accountId]);
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
