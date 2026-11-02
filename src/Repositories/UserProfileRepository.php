<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

/**
 * UserProfileRepository
 *
 * Dedicated data access layer for the user_profiles table.
 * Strictly encapsulates all DB operations for extended personal profile attributes.
 */
class UserProfileRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retrieve the extended profile record for a user.
     *
     * @param int $userId
     * @return array|null
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, user_id, phone, date_of_birth, gender, country, city, address, created_at, updated_at
            FROM user_profiles
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Insert or update extended profile attributes for a user.
     *
     * @param int $userId
     * @param array{
     *     phone?: ?string,
     *     date_of_birth?: ?string,
     *     gender?: ?string,
     *     country?: ?string,
     *     city?: ?string,
     *     address?: ?string
     * } $data
     * @return bool
     */
    public function upsert(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_profiles
                (user_id, phone, date_of_birth, gender, country, city, address, updated_at)
            VALUES
                (:user_id, :phone, :date_of_birth, :gender, :country, :city, :address, NOW())
            ON DUPLICATE KEY UPDATE
                phone         = VALUES(phone),
                date_of_birth = VALUES(date_of_birth),
                gender        = VALUES(gender),
                country       = VALUES(country),
                city          = VALUES(city),
                address       = VALUES(address),
                updated_at    = NOW()
        ");

        return $stmt->execute([
            ':user_id'       => $userId,
            ':phone'         => $data['phone'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':gender'        => $data['gender'] ?? null,
            ':country'       => $data['country'] ?? null,
            ':city'          => $data['city'] ?? null,
            ':address'       => $data['address'] ?? null,
        ]);
    }

    /**
     * Delete an extended profile record by user ID.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_profiles WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

