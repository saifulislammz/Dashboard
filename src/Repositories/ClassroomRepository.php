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
            SELECT c.*, 
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

    public function getPaginatedClassrooms(int $limit, int $offset, string $search = ''): array
    {
        $query = "
            SELECT c.id, c.class_code, c.class_name, c.class_title, c.status, c.created_at,
                   t.username as teacher_name, t.email as teacher_email,
                   s.username as student_name, s.email as student_email
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
        ";

        $params = [];

        if (!empty($search)) {
            $query .= " WHERE c.class_name LIKE :search1 
                           OR c.class_title LIKE :search2 
                           OR t.username LIKE :search3 
                           OR s.username LIKE :search4";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "%$search%";
            $params['search4'] = "%$search%";
        }

        $query .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";

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

    public function countTotalClassrooms(string $search = ''): int
    {
        $query = "
            SELECT COUNT(c.id) as total
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
        ";
        
        $params = [];
        if (!empty($search)) {
            $query .= " WHERE c.class_name LIKE :search1 
                           OR c.class_title LIKE :search2 
                           OR t.username LIKE :search3 
                           OR s.username LIKE :search4";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "%$search%";
            $params['search4'] = "%$search%";
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
