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
            'class_code'  => $data['class_code'],
            'class_name'  => $data['class_name'],
            'class_title' => $data['class_title'],
            'teacher_id'  => $data['teacher_id'] ?? null,
            'student_id'  => $data['student_id'] ?? null,
            'status'      => $data['status'],
            'created_by'  => $data['created_by']
        ]);
    }

    public function getLastInsertedId(): int
    {
        return (int) $this->db->lastInsertId();
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
            'class_name'  => $data['class_name'],
            'class_title' => $data['class_title'],
            'status'      => $data['status'],
            'id'          => $id
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
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classroom) {
            return null;
        }

        // Fetch assigned teachers and students from pivot tables
        $classroom['teachers'] = $this->getAssignedTeachers($id);
        $classroom['students'] = $this->getAssignedStudents($id);

        // Populate fallback teacher_name if empty but teachers exist
        if (empty($classroom['teacher_name']) && !empty($classroom['teachers'])) {
            $classroom['teacher_name']  = $classroom['teachers'][0]['username'];
            $classroom['teacher_email'] = $classroom['teachers'][0]['email'];
        }

        // Populate fallback student_name if empty but students exist
        if (empty($classroom['student_name']) && !empty($classroom['students'])) {
            $classroom['student_name']  = $classroom['students'][0]['username'];
            $classroom['student_email'] = $classroom['students'][0]['email'];
        }

        return $classroom;
    }

    public function findByClassCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT id, class_code, class_name FROM classrooms WHERE class_code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // -------------------------------------------------------------------------
    // MULTI-TEACHER & MULTI-STUDENT ASSIGNMENTS
    // -------------------------------------------------------------------------

    public function syncTeachers(int $classroomId, array $teacherIds, ?int $primaryTeacherId = null): void
    {
        $cleanTeacherIds = array_values(array_unique(array_filter(array_map('intval', $teacherIds), fn($id) => $id > 0)));

        // If no primary is provided, make the first teacher primary
        if ($primaryTeacherId === null || !in_array($primaryTeacherId, $cleanTeacherIds, true)) {
            $primaryTeacherId = !empty($cleanTeacherIds) ? $cleanTeacherIds[0] : null;
        }

        // Delete existing mappings
        $stmtDel = $this->db->prepare("DELETE FROM classroom_teachers WHERE classroom_id = :classroom_id");
        $stmtDel->execute(['classroom_id' => $classroomId]);

        // Insert new mappings
        if (!empty($cleanTeacherIds)) {
            $stmtIns = $this->db->prepare("
                INSERT INTO classroom_teachers (classroom_id, user_id, is_primary) 
                VALUES (:classroom_id, :user_id, :is_primary)
            ");
            foreach ($cleanTeacherIds as $tId) {
                $isPrimary = ($tId === $primaryTeacherId) ? 1 : 0;
                $stmtIns->execute([
                    'classroom_id' => $classroomId,
                    'user_id'      => $tId,
                    'is_primary'   => $isPrimary
                ]);
            }
        }

        // Update legacy column for backward compatibility
        $stmtLegacy = $this->db->prepare("UPDATE classrooms SET teacher_id = :primary_id WHERE id = :id");
        $stmtLegacy->execute([
            'primary_id' => $primaryTeacherId,
            'id'         => $classroomId
        ]);
    }

    public function syncStudents(int $classroomId, array $studentIds): void
    {
        $cleanStudentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds), fn($id) => $id > 0)));

        // Delete existing mappings
        $stmtDel = $this->db->prepare("DELETE FROM classroom_students WHERE classroom_id = :classroom_id");
        $stmtDel->execute(['classroom_id' => $classroomId]);

        // Insert new mappings
        if (!empty($cleanStudentIds)) {
            $stmtIns = $this->db->prepare("
                INSERT INTO classroom_students (classroom_id, user_id, status) 
                VALUES (:classroom_id, :user_id, 'active')
            ");
            foreach ($cleanStudentIds as $sId) {
                $stmtIns->execute([
                    'classroom_id' => $classroomId,
                    'user_id'      => $sId
                ]);
            }
        }

        // Update legacy column for backward compatibility with first student or null
        $firstStudentId = !empty($cleanStudentIds) ? $cleanStudentIds[0] : null;
        $stmtLegacy = $this->db->prepare("UPDATE classrooms SET student_id = :student_id WHERE id = :id");
        $stmtLegacy->execute([
            'student_id' => $firstStudentId,
            'id'         => $classroomId
        ]);
    }

    public function getAssignedTeachers(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT ct.user_id, ct.is_primary, ct.created_at, u.username, u.email
            FROM classroom_teachers ct
            JOIN users u ON u.id = ct.user_id
            WHERE ct.classroom_id = :classroom_id
            ORDER BY ct.is_primary DESC, u.username ASC
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignedTeacherIds(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM classroom_teachers 
            WHERE classroom_id = :classroom_id
            ORDER BY is_primary DESC, user_id ASC
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getAssignedStudents(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.user_id, cs.status, cs.enrolled_at, u.username, u.email
            FROM classroom_students cs
            JOIN users u ON u.id = cs.user_id
            WHERE cs.classroom_id = :classroom_id
            ORDER BY u.username ASC
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignedStudentIds(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM classroom_students 
            WHERE classroom_id = :classroom_id
            ORDER BY user_id ASC
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isTeacherInClassroom(int $classroomId, int $teacherId): bool
    {
        $stmt = $this->db->prepare("
            SELECT (
                EXISTS (SELECT 1 FROM classroom_teachers WHERE classroom_id = :cid AND user_id = :tid)
                OR EXISTS (SELECT 1 FROM classrooms WHERE id = :cid2 AND teacher_id = :tid2)
            ) as is_member
        ");
        $stmt->execute([
            'cid'  => $classroomId,
            'tid'  => $teacherId,
            'cid2' => $classroomId,
            'tid2' => $teacherId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function isStudentInClassroom(int $classroomId, int $studentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT (
                EXISTS (SELECT 1 FROM classroom_students WHERE classroom_id = :cid AND user_id = :sid)
                OR EXISTS (SELECT 1 FROM classrooms WHERE id = :cid2 AND student_id = :sid2)
            ) as is_member
        ");
        $stmt->execute([
            'cid'  => $classroomId,
            'sid'  => $studentId,
            'cid2' => $classroomId,
            'sid2' => $studentId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // PAGINATION & LISTING (Admin, Teacher, Student)
    // -------------------------------------------------------------------------

    public function getPaginatedClassrooms(
        int $limit, 
        int $offset, 
        string $search = '', 
        string $sort = 'created_at', 
        string $order = 'DESC',
        ?array $allowedClassroomIds = null
    ): array {
        if ($allowedClassroomIds !== null && empty($allowedClassroomIds)) {
            return [];
        }

        $query = "
            SELECT c.id, c.class_code, c.class_name, c.class_title, c.status, c.created_at,
                   c.teacher_id, c.student_id,
                   t.username as teacher_name, t.email as teacher_email,
                   s.username as student_name, s.email as student_email,
                   (SELECT COUNT(*) FROM classroom_teachers ct WHERE ct.classroom_id = c.id) AS teacher_count,
                   (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id) AS student_count
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            LEFT JOIN users s ON c.student_id = s.id
        ";

        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "(
                CONCAT_WS(' ', c.class_name, c.class_title, c.class_code, t.username, s.username) LIKE :search
                OR EXISTS (
                    SELECT 1 FROM classroom_teachers ct2 
                    JOIN users ut ON ut.id = ct2.user_id 
                    WHERE ct2.classroom_id = c.id AND ut.username LIKE :search
                )
                OR EXISTS (
                    SELECT 1 FROM classroom_students cs2 
                    JOIN users us ON us.id = cs2.user_id 
                    WHERE cs2.classroom_id = c.id AND us.username LIKE :search
                )
            )";
            $params['search'] = "%$search%";
        }

        if ($allowedClassroomIds !== null && !empty($allowedClassroomIds)) {
            $sanitizedIds = implode(',', array_map('intval', $allowedClassroomIds));
            $whereConditions[] = "c.id IN ($sanitizedIds)";
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $sortMap = [
            'id'         => 'c.id',
            'status'     => 'c.status',
            'created_at' => 'c.created_at'
        ];
        $orderBy  = $sortMap[$sort] ?? 'c.created_at';
        $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $query .= " ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enhance with primary teacher name if teacher_name is null
        foreach ($classrooms as &$cls) {
            if (empty($cls['teacher_name']) && (int) $cls['teacher_count'] > 0) {
                $teachers = $this->getAssignedTeachers((int) $cls['id']);
                if (!empty($teachers)) {
                    $cls['teacher_name']  = $teachers[0]['username'];
                    $cls['teacher_email'] = $teachers[0]['email'];
                }
            }
        }
        unset($cls);

        return $classrooms;
    }

    public function countTotalClassrooms(string $search = '', ?array $allowedClassroomIds = null): int
    {
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
            $whereConditions[] = "(
                CONCAT_WS(' ', c.class_name, c.class_title, c.class_code, t.username, s.username) LIKE :search
                OR EXISTS (
                    SELECT 1 FROM classroom_teachers ct2 
                    JOIN users ut ON ut.id = ct2.user_id 
                    WHERE ct2.classroom_id = c.id AND ut.username LIKE :search
                )
                OR EXISTS (
                    SELECT 1 FROM classroom_students cs2 
                    JOIN users us ON us.id = cs2.user_id 
                    WHERE cs2.classroom_id = c.id AND us.username LIKE :search
                )
            )";
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
                   c.teacher_id, c.student_id,
                   s.username as student_name, s.email as student_email,
                   (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id) AS student_count,
                   (SELECT COUNT(*) FROM classroom_teachers ct WHERE ct.classroom_id = c.id) AS teacher_count
            FROM classrooms c
            LEFT JOIN users s ON c.student_id = s.id
            WHERE c.teacher_id = :teacher_id
               OR EXISTS (SELECT 1 FROM classroom_teachers ct WHERE ct.classroom_id = c.id AND ct.user_id = :teacher_id2)
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':teacher_id',  $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':teacher_id2', $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,     PDO::PARAM_INT);
        $stmt->bindValue(':offset',      $offset,    PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTeacherClassrooms(int $teacherId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(c.id) 
            FROM classrooms c 
            WHERE c.teacher_id = :teacher_id
               OR EXISTS (SELECT 1 FROM classroom_teachers ct WHERE ct.classroom_id = c.id AND ct.user_id = :teacher_id2)
        ");
        $stmt->execute([
            'teacher_id'  => $teacherId,
            'teacher_id2' => $teacherId
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function getStudentClassrooms(int $studentId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.class_code, c.class_name, c.class_title, c.status, c.created_at,
                   c.teacher_id, c.student_id,
                   t.username as teacher_name, t.email as teacher_email,
                   (SELECT COUNT(*) FROM classroom_teachers ct WHERE ct.classroom_id = c.id) AS teacher_count,
                   (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id) AS student_count
            FROM classrooms c
            LEFT JOIN users t ON c.teacher_id = t.id
            WHERE c.student_id = :student_id
               OR EXISTS (SELECT 1 FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.user_id = :student_id2)
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':student_id',  $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id2', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,     PDO::PARAM_INT);
        $stmt->bindValue(':offset',      $offset,    PDO::PARAM_INT);
        $stmt->execute();
        
        $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enhance with primary teacher name if teacher_name is null
        foreach ($classrooms as &$cls) {
            if (empty($cls['teacher_name']) && (int) $cls['teacher_count'] > 0) {
                $teachers = $this->getAssignedTeachers((int) $cls['id']);
                if (!empty($teachers)) {
                    $cls['teacher_name']  = $teachers[0]['username'];
                    $cls['teacher_email'] = $teachers[0]['email'];
                }
            }
        }
        unset($cls);

        return $classrooms;
    }

    public function countStudentClassrooms(int $studentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(c.id) 
            FROM classrooms c 
            WHERE c.student_id = :student_id
               OR EXISTS (SELECT 1 FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.user_id = :student_id2)
        ");
        $stmt->execute([
            'student_id'  => $studentId,
            'student_id2' => $studentId
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function isClassCodeUnique(string $code): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM classrooms WHERE class_code = :code");
        $stmt->execute(['code' => $code]);
        return (int) $stmt->fetchColumn() === 0;
    }
}
