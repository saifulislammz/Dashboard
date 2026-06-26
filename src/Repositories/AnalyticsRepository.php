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

    public function getStudentCount(): int
    {
        return Cache::remember('total_students', 300, function() {
            require_once __DIR__ . '/../config/roles.php';
            
            $roleStudent = \App\Config\Roles::STUDENT;
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE (roles_mask & {$roleStudent}) = {$roleStudent}");
            return (int) $stmt->fetchColumn();
        });
    }

    public function getTeacherCount(): int
    {
        return Cache::remember('total_teachers', 300, function() {
            require_once __DIR__ . '/../config/roles.php';
            
            $roleTeacher = \App\Config\Roles::TEACHER;
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE (roles_mask & {$roleTeacher}) = {$roleTeacher}");
            return (int) $stmt->fetchColumn();
        });
    }

    public function getNoticeCount(): int
    {
        return Cache::remember('total_notices', 300, function() {
            $stmt = $this->db->query("SELECT COUNT(*) FROM notices");
            return (int) $stmt->fetchColumn();
        });
    }

    public function getClassroomCount(): int
    {
        return Cache::remember('total_classrooms', 300, function() {
            $stmt = $this->db->query("SELECT COUNT(*) FROM classrooms");
            return (int) $stmt->fetchColumn();
        });
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
