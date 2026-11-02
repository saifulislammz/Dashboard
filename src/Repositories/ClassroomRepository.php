<?php

namespace App\Repositories;

use PDO;

class ClassroomRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO classrooms 
            (class_code, class_name, class_title, teacher_id, student_id, status, created_by)
            VALUES 
            (:class_code, :class_name, :class_title, :teacher_id, :student_id, :status, :created_by)
        ");

        return $stmt->execute([
            'class_code' => $data['class_code'],
            'class_name' => $data['class_name'],
            'class_title' => $data['class_title'],
            'teacher_id' => $data['teacher_id'],
            'student_id' => $data['student_id'],
            'status' => $data['status'],
            'created_by' => $data['created_by']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE classrooms 
            SET class_name = :class_name, 
                class_title = :class_title, 
                status = :status
            WHERE id = :id
        ");

        return $stmt->execute([
            'class_name' => $data['class_name'],
            'class_title' => $data['class_title'],
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM classrooms WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.class_code, c.class_name, c.class_title, 
                   c.teacher_id, c.student_id, c.status, c.created_by, 
                   c.created_at, c.updated_at, 
                   t.username as teacher_name, t.email as teacher_email,
                   s.username as student_name, s.email as student_email
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function findByClassCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT id, class_code, class_name FROM classrooms WHERE class_code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPaginatedClassrooms(
        int $limit, 
        int $offset, 
        string $search = '', 
        string $sort = 'created_at', 
        string $order = 'DESC',
        ?array $allowedClassroomIds = null
    ): array {
        // If an explicit scope was passed and it is empty, return early with zero results
        if ($allowedClassroomIds !== null && empty($allowedClassroomIds)) {
            return [];
        }

        $query = "
            SELECT c.id, c.class_code, c.class_name, c.class_title, c.status, c.created_at,
                   t.username as teacher_name, t.email as teacher_email,
                   s.username as student_name, s.email as student_email
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
        ";

        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "CONCAT_WS(' ', c.class_name, c.class_title, t.username, s.username) LIKE :search";
            $params['search'] = "%$search%";
        }

        if ($allowedClassroomIds !== null && !empty($allowedClassroomIds)) {
            $sanitizedIds = implode(',', array_map('intval', $allowedClassroomIds));
            $whereConditions[] = "c.id IN ($sanitizedIds)";
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }

        // Map allowed sort columns to table aliases to prevent SQL injection
        $sortMap = [
            'id' => 'c.id',
            'status' => 'c.status',
            'created_at' => 'c.created_at'
        ];
        $orderBy = $sortMap[$sort] ?? 'c.created_at';
        $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $query .= " ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);

        // Bind limit/offset as ints to avoid PDO string quoting issues
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotalClassrooms(string $search = '', ?array $allowedClassroomIds = null): int
    {
        // If an explicit scope was passed and it is empty, return 0
        if ($allowedClassroomIds !== null && empty($allowedClassroomIds)) {
            return 0;
        }

        $query = "
            SELECT COUNT(c.id) as total
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
        ";
        
        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "CONCAT_WS(' ', c.class_name, c.class_title, t.username, s.username) LIKE :search";
            $params['search'] = "%$search%";
        }

        if ($allowedClassroomIds !== null && !empty($allowedClassroomIds)) {
            $sanitizedIds = implode(',', array_map('intval', $allowedClassroomIds));
            $whereConditions[] = "c.id IN ($sanitizedIds)";
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getTeacherClassrooms(int $teacherId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.class_code, c.class_name, c.class_title, c.status, c.created_at,
                   s.username as student_name, s.email as student_email
            FROM classrooms c
            LEFT JOIN users s ON c.student_id = s.id
            WHERE c.teacher_id = :teacher_id
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':teacher_id', $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTeacherClassrooms(int $teacherId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM classrooms WHERE teacher_id = :teacher_id");
        $stmt->execute(['teacher_id' => $teacherId]);
        return (int) $stmt->fetchColumn();
    }

    public function getStudentClassrooms(int $studentId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.class_code, c.class_name, c.class_title, c.status, c.created_at,
                   t.username as teacher_name, t.email as teacher_email
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            WHERE c.student_id = :student_id
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countStudentClassrooms(int $studentId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM classrooms WHERE student_id = :student_id");
        $stmt->execute(['student_id' => $studentId]);
        return (int) $stmt->fetchColumn();
    }

    public function isClassCodeUnique(string $code): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM classrooms WHERE class_code = :code");
        $stmt->execute(['code' => $code]);
        return (int) $stmt->fetchColumn() === 0;
    }
}
