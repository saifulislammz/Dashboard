<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * ClassroomResourceRepository
 * Handles all database operations for classroom resources, multi-group distribution mappings,
 * and assignment submissions using PDO prepared statements.
 */
class ClassroomResourceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new classroom resource master record.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO classroom_resources (
                    title, description, resource_type, file_path, original_name, 
                    file_size, mime_type, external_url, quiz_id, due_date, 
                    max_points, target_audience, status, created_by
                ) VALUES (
                    :title, :description, :resource_type, :file_path, :original_name, 
                    :file_size, :mime_type, :external_url, :quiz_id, :due_date, 
                    :max_points, :target_audience, :status, :created_by
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title'           => $data['title'],
            ':description'     => $data['description'] ?? null,
            ':resource_type'   => $data['resource_type'] ?? 'document',
            ':file_path'       => $data['file_path'] ?? null,
            ':original_name'   => $data['original_name'] ?? null,
            ':file_size'       => $data['file_size'] ?? 0,
            ':mime_type'       => $data['mime_type'] ?? null,
            ':external_url'    => $data['external_url'] ?? null,
            ':quiz_id'         => !empty($data['quiz_id']) ? (int) $data['quiz_id'] : null,
            ':due_date'        => !empty($data['due_date']) ? $data['due_date'] : null,
            ':max_points'      => isset($data['max_points']) && $data['max_points'] !== '' ? (float) $data['max_points'] : null,
            ':target_audience' => $data['target_audience'] ?? 'all',
            ':status'          => $data['status'] ?? 'published',
            ':created_by'      => (int) $data['created_by'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing classroom resource.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE classroom_resources SET 
                    title = :title,
                    description = :description,
                    resource_type = :resource_type,
                    external_url = :external_url,
                    quiz_id = :quiz_id,
                    due_date = :due_date,
                    max_points = :max_points,
                    target_audience = :target_audience,
                    status = :status
                WHERE id = :id AND deleted_at IS NULL";

        $params = [
            ':id'              => $id,
            ':title'           => $data['title'],
            ':description'     => $data['description'] ?? null,
            ':resource_type'   => $data['resource_type'] ?? 'document',
            ':external_url'    => $data['external_url'] ?? null,
            ':quiz_id'         => !empty($data['quiz_id']) ? (int) $data['quiz_id'] : null,
            ':due_date'        => !empty($data['due_date']) ? $data['due_date'] : null,
            ':max_points'      => isset($data['max_points']) && $data['max_points'] !== '' ? (float) $data['max_points'] : null,
            ':target_audience' => $data['target_audience'] ?? 'all',
            ':status'          => $data['status'] ?? 'published',
        ];

        if (!empty($data['file_path'])) {
            $sql = str_replace(
                "WHERE id = :id",
                ", file_path = :file_path, original_name = :original_name, file_size = :file_size, mime_type = :mime_type WHERE id = :id",
                $sql
            );
            $params[':file_path']     = $data['file_path'];
            $params[':original_name'] = $data['original_name'] ?? null;
            $params[':file_size']     = $data['file_size'] ?? 0;
            $params[':mime_type']     = $data['mime_type'] ?? null;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft delete a resource.
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE classroom_resources SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find a resource by its ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT cr.*, 
                       u.username AS creator_username,
                       COALESCE(up.phone, '') AS creator_phone,
                       q.title AS quiz_title
                FROM classroom_resources cr
                LEFT JOIN users u ON cr.created_by = u.id
                LEFT JOIN user_profiles up ON u.id = up.user_id
                LEFT JOIN quizzes q ON cr.quiz_id = q.id
                WHERE cr.id = :id AND cr.deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Distribute resource to one or multiple classrooms in bulk.
     * Uses atomic INSERT IGNORE to prevent duplicate mapping errors.
     */
    public function distributeToClassrooms(int $resourceId, array $classroomIds): bool
    {
        if (empty($classroomIds)) {
            return false;
        }

        $sql = "INSERT IGNORE INTO classroom_resource_distributions (resource_id, classroom_id) VALUES ";
        $placeholders = [];
        $values = [];

        foreach ($classroomIds as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                $placeholders[] = "(?, ?)";
                $values[] = $resourceId;
                $values[] = $cid;
            }
        }

        if (empty($placeholders)) {
            return false;
        }

        $sql .= implode(', ', $placeholders);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Synchronize distributions for a resource (remove old, insert new).
     */
    public function syncDistributions(int $resourceId, array $classroomIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM classroom_resource_distributions WHERE resource_id = :rid");
            $stmtDel->execute([':rid' => $resourceId]);

            if (!empty($classroomIds)) {
                $this->distributeToClassrooms($resourceId, $classroomIds);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get list of classroom IDs where this resource has been distributed.
     */
    public function getDistributedClassroomIds(int $resourceId): array
    {
        $stmt = $this->db->prepare("SELECT classroom_id FROM classroom_resource_distributions WHERE resource_id = :rid");
        $stmt->execute([':rid' => $resourceId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Get list of classrooms with details where this resource has been distributed.
     */
    public function getDistributedClassrooms(int $resourceId): array
    {
        $sql = "SELECT c.id, c.class_name, c.class_title, c.class_code, c.status
                FROM classroom_resource_distributions crd
                INNER JOIN classrooms c ON crd.classroom_id = c.id
                WHERE crd.resource_id = :rid
                ORDER BY c.class_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $resourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Fetch paginated resources for administrative / management views.
     */
    public function getPaginatedResources(
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?string $resourceType = null,
        ?int $classroomId = null,
        ?int $creatorId = null
    ): array {
        $offset = max(0, ($page - 1) * $limit);
        $where = ["cr.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(cr.title LIKE :search1 OR cr.description LIKE :search2)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if (!empty($resourceType)) {
            $where[] = "cr.resource_type = :rtype";
            $params[':rtype'] = $resourceType;
        }

        if (!empty($creatorId)) {
            $where[] = "cr.created_by = :creator";
            $params[':creator'] = $creatorId;
        }

        if (!empty($classroomId)) {
            $where[] = "EXISTS (
                SELECT 1 FROM classroom_resource_distributions crd2 
                WHERE crd2.resource_id = cr.id AND crd2.classroom_id = :cid
            )";
            $params[':cid'] = $classroomId;
        }

        $whereClause = implode(" AND ", $where);

        // Count query
        $countSql = "SELECT COUNT(*) FROM classroom_resources cr WHERE $whereClause";
        $stmtCount = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $totalItems = (int) $stmtCount->fetchColumn();
        $totalPages = (int) ceil($totalItems / $limit);

        // Main query
        $sql = "SELECT cr.*, 
                       u.username AS creator_username,
                       (SELECT COUNT(*) FROM classroom_resource_distributions crd WHERE crd.resource_id = cr.id) AS distribution_count,
                       (SELECT COUNT(*) FROM classroom_assignment_submissions cas WHERE cas.resource_id = cr.id) AS submission_count
                FROM classroom_resources cr
                LEFT JOIN users u ON cr.created_by = u.id
                WHERE $whereClause
                ORDER BY cr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total'        => $totalItems,
            'pages'        => $totalPages,
            'current_page' => $page,
        ];
    }

    /**
     * Get resources distributed to a specific classroom (for classroom_details.php).
     */
    public function getResourcesForClassroom(int $classroomId, string $audienceRole = 'all', int $limit = 50, int $offset = 0): array
    {
        $audienceFilter = "cr.target_audience IN ('all', :audience)";
        if ($audienceRole === 'all' || $audienceRole === 'admin') {
            $audienceFilter = "1=1";
        }

        $sql = "SELECT cr.*, 
                       u.username AS creator_username,
                       q.title AS quiz_title
                FROM classroom_resource_distributions crd
                INNER JOIN classroom_resources cr 
                    ON crd.resource_id = cr.id AND cr.deleted_at IS NULL AND cr.status = 'published'
                LEFT JOIN users u ON cr.created_by = u.id
                LEFT JOIN quizzes q ON cr.quiz_id = q.id
                WHERE crd.classroom_id = :classroom_id 
                  AND $audienceFilter
                ORDER BY cr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':classroom_id', $classroomId, PDO::PARAM_INT);
        if ($audienceFilter !== "1=1") {
            $stmt->bindValue(':audience', $audienceRole, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all resources across enrolled classrooms for a student.
     */
    public function getStudentAllResources(
        int $studentId,
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?string $resourceType = null,
        ?int $classroomId = null
    ): array {
        $offset = max(0, ($page - 1) * $limit);
        $where = [
            "cr.deleted_at IS NULL",
            "cr.status = 'published'",
            "cr.target_audience IN ('all', 'students')",
            "EXISTS (
                SELECT 1 
                FROM classroom_resource_distributions crd
                INNER JOIN classroom_students cs 
                    ON crd.classroom_id = cs.classroom_id
                WHERE crd.resource_id = cr.id 
                  AND cs.user_id = :student_id 
                  AND cs.status = 'active'
            )"
        ];
        $params = [':student_id' => $studentId];

        if (!empty($search)) {
            $where[] = "(cr.title LIKE :search1 OR cr.description LIKE :search2)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if (!empty($resourceType)) {
            $where[] = "cr.resource_type = :rtype";
            $params[':rtype'] = $resourceType;
        }

        if (!empty($classroomId)) {
            $where[] = "EXISTS (
                SELECT 1 FROM classroom_resource_distributions crd3 
                WHERE crd3.resource_id = cr.id AND crd3.classroom_id = :cid
            )";
            $params[':cid'] = $classroomId;
        }

        $whereClause = implode(" AND ", $where);

        $countSql = "SELECT COUNT(DISTINCT cr.id) FROM classroom_resources cr WHERE $whereClause";
        $stmtCount = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $totalItems = (int) $stmtCount->fetchColumn();
        $totalPages = (int) ceil($totalItems / $limit);

        $sql = "SELECT cr.*, 
                       u.username AS creator_username,
                       q.title AS quiz_title,
                       cas.id AS my_submission_id,
                       cas.status AS my_submission_status,
                       cas.points_awarded AS my_grade,
                       cas.submitted_at AS my_submitted_at
                FROM classroom_resources cr
                LEFT JOIN users u ON cr.created_by = u.id
                LEFT JOIN quizzes q ON cr.quiz_id = q.id
                LEFT JOIN classroom_assignment_submissions cas 
                    ON cas.resource_id = cr.id AND cas.student_id = :student_id2
                WHERE $whereClause
                ORDER BY cr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':student_id2', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total'        => $totalItems,
            'pages'        => $totalPages,
            'current_page' => $page,
        ];
    }

    /**
     * Verify if a user is authorized to access / download a resource.
     * IDOR-safe single query execution.
     */
    public function canUserAccessResource(int $resourceId, int $userId, int $roleBitmask): bool
    {
        $isAdminRole   = (defined('ROLE_ADMIN') && ($roleBitmask & ROLE_ADMIN)) || ($roleBitmask === 1);
        $isTeacherRole = (defined('ROLE_TEACHER') && ($roleBitmask & ROLE_TEACHER)) || ($roleBitmask === 2);
        $isStudentRole = (defined('ROLE_STUDENT') && ($roleBitmask & ROLE_STUDENT)) || ($roleBitmask === 4);

        // 1. Super Admin is always authorized
        if ($isAdminRole) {
            return true;
        }

        // 2. Creator is always authorized
        $stmtCreator = $this->db->prepare("SELECT created_by FROM classroom_resources WHERE id = :id AND deleted_at IS NULL");
        $stmtCreator->execute([':id' => $resourceId]);
        $creatorId = (int) $stmtCreator->fetchColumn();
        if ($creatorId === $userId) {
            return true;
        }

        // 3. If Teacher, check if assigned to any classroom where this resource is distributed
        if ($isTeacherRole) {
            $sqlT = "SELECT 1 
                     FROM classroom_resource_distributions crd
                     INNER JOIN classroom_teachers ct ON crd.classroom_id = ct.classroom_id
                     WHERE crd.resource_id = :rid AND ct.user_id = :uid
                     LIMIT 1";
            $stmtT = $this->db->prepare($sqlT);
            $stmtT->execute([':rid' => $resourceId, ':uid' => $userId]);
            if ($stmtT->fetchColumn()) {
                return true;
            }
        }

        // 4. If Student, check if active in any classroom where this resource is distributed & target_audience permits
        if ($isStudentRole) {
            $sqlS = "SELECT 1 
                     FROM classroom_resource_distributions crd
                     INNER JOIN classroom_resources cr ON crd.resource_id = cr.id
                     INNER JOIN classroom_students cs ON crd.classroom_id = cs.classroom_id
                     WHERE crd.resource_id = :rid 
                       AND cs.user_id = :uid 
                       AND cs.status = 'active'
                       AND cr.status = 'published'
                       AND cr.target_audience IN ('all', 'students')
                     LIMIT 1";
            $stmtS = $this->db->prepare($sqlS);
            $stmtS->execute([':rid' => $resourceId, ':uid' => $userId]);
            if ($stmtS->fetchColumn()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Submit an assignment (insert or update).
     */
    public function saveAssignmentSubmission(array $data): int
    {
        $sql = "INSERT INTO classroom_assignment_submissions (
                    resource_id, student_id, notes, file_path, original_name, status, submitted_at
                ) VALUES (
                    :resource_id, :student_id, :notes, :file_path, :original_name, 'submitted', NOW()
                ) ON DUPLICATE KEY UPDATE
                    notes = VALUES(notes),
                    file_path = VALUES(file_path),
                    original_name = VALUES(original_name),
                    submitted_at = NOW(),
                    status = 'submitted'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':resource_id'   => (int) $data['resource_id'],
            ':student_id'    => (int) $data['student_id'],
            ':notes'         => $data['notes'] ?? null,
            ':file_path'     => $data['file_path'] ?? null,
            ':original_name' => $data['original_name'] ?? null,
        ]);

        return (int) ($this->db->lastInsertId() ?: $this->getSubmissionId((int)$data['resource_id'], (int)$data['student_id']));
    }

    /**
     * Get submission ID for a student and resource.
     */
    public function getSubmissionId(int $resourceId, int $studentId): int
    {
        $stmt = $this->db->prepare("SELECT id FROM classroom_assignment_submissions WHERE resource_id = :rid AND student_id = :sid");
        $stmt->execute([':rid' => $resourceId, ':sid' => $studentId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get a single student submission.
     */
    public function getSubmission(int $resourceId, int $studentId): ?array
    {
        $sql = "SELECT cas.*, u.username AS student_name, u.email AS student_email
                FROM classroom_assignment_submissions cas
                INNER JOIN users u ON cas.student_id = u.id
                WHERE cas.resource_id = :rid AND cas.student_id = :sid
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $resourceId, ':sid' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get all submissions for an assignment resource.
     */
    public function getSubmissionsByResource(int $resourceId): array
    {
        $sql = "SELECT cas.*, 
                       u.username AS student_name, 
                       u.email AS student_email,
                       g.username AS grader_name
                FROM classroom_assignment_submissions cas
                INNER JOIN users u ON cas.student_id = u.id
                LEFT JOIN users g ON cas.graded_by = g.id
                WHERE cas.resource_id = :rid
                ORDER BY cas.submitted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $resourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Grade a student submission.
     */
    public function gradeSubmission(int $submissionId, float $points, ?string $feedback, int $gradedBy): bool
    {
        $sql = "UPDATE classroom_assignment_submissions SET
                    points_awarded = :points,
                    teacher_feedback = :feedback,
                    graded_by = :graded_by,
                    graded_at = NOW(),
                    status = 'graded'
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'        => $submissionId,
            ':points'    => $points,
            ':feedback'  => $feedback,
            ':graded_by' => $gradedBy,
        ]);
    }
}
