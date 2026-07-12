<?php

namespace App\Repositories;

use PDO;
use App\Utils\Cache;

class AnalyticsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all dashboard counts in a single query.
     * Returns: [total_students, total_teachers, total_notices, total_classrooms]
     */
    public function getDashboardCounts(): array
    {
        require_once __DIR__ . '/../config/roles.php';

        $roleStudent = \App\Config\Roles::STUDENT;
        $roleTeacher = \App\Config\Roles::TEACHER;

        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM users      WHERE (roles_mask & {$roleStudent}) = {$roleStudent}) AS total_students,
                (SELECT COUNT(*) FROM users      WHERE (roles_mask & {$roleTeacher}) = {$roleTeacher}) AS total_teachers,
                (SELECT COUNT(*) FROM notices)                                                          AS total_notices,
                (SELECT COUNT(*) FROM classrooms)                                                       AS total_classrooms,
                (SELECT COUNT(*) FROM quizzes WHERE status = 'active' AND deleted_at IS NULL)           AS total_quizzes
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_students'   => (int) ($row['total_students']   ?? 0),
            'total_teachers'   => (int) ($row['total_teachers']   ?? 0),
            'total_notices'    => (int) ($row['total_notices']    ?? 0),
            'total_classrooms' => (int) ($row['total_classrooms'] ?? 0),
            'total_quizzes'    => (int) ($row['total_quizzes']    ?? 0),
        ];
    }

    public function getRecentClassrooms(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.class_code, c.class_name, c.status, c.created_at,
                   t.username as teacher_name, s.username as student_name
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
            ORDER BY c.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
