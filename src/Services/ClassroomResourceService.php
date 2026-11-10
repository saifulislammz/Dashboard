<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClassroomResourceRepository;
use Exception;
use PDO;

/**
 * ClassroomResourceService
 * Orchestrates business logic, file security inspection, authorization checks,
 * atomic bulk distribution, and student assignment submissions.
 */
class ClassroomResourceService
{
    private ClassroomResourceRepository $repository;
    private PDO $db;

    private const ALLOWED_RESOURCE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 
        'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'webp', 
        'mp4', 'webm', 'mp3', 'csv'
    ];

    private const MAX_RESOURCE_FILE_SIZE = 52428800; // 50MB in bytes
    private const MAX_SUBMISSION_FILE_SIZE = 26214400; // 25MB in bytes

    public function __construct(ClassroomResourceRepository $repository, PDO $db)
    {
        $this->repository = $repository;
        $this->db = $db;
    }

    /**
     * Create and distribute a resource to one or multiple group classrooms in one click.
     */
    public function createAndDistribute(array $data, ?array $file, int $creatorId, bool $isAdmin = false): int
    {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $resourceType = strtolower(trim($data['resource_type'] ?? 'document'));
        $targetAudience = strtolower(trim($data['target_audience'] ?? 'all'));
        $externalUrl = trim($data['external_url'] ?? '');
        $quizId = !empty($data['quiz_id']) ? (int) $data['quiz_id'] : null;
        $dueDate = !empty($data['due_date']) ? trim($data['due_date']) : null;
        $maxPoints = isset($data['max_points']) && $data['max_points'] !== '' ? (float) $data['max_points'] : null;
        $status = ($data['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        // 1. Validation
        if (empty($title)) {
            throw new Exception("Resource title is required.");
        }

        $validTypes = ['document', 'video', 'assignment', 'quiz', 'link'];
        if (!in_array($resourceType, $validTypes, true)) {
            throw new Exception("Invalid resource type selected.");
        }

        $validAudiences = ['all', 'students', 'teachers'];
        if (!in_array($targetAudience, $validAudiences, true)) {
            $targetAudience = 'all';
        }

        // Classroom target validation
        $rawClassroomIds = $data['classroom_ids'] ?? [];
        if (!is_array($rawClassroomIds) || empty($rawClassroomIds)) {
            throw new Exception("Please select at least one Group Classroom for distribution.");
        }

        $classroomIds = array_values(array_unique(array_filter(array_map('intval', $rawClassroomIds))));
        if (empty($classroomIds)) {
            throw new Exception("Invalid classrooms selected.");
        }

        // Non-admin teacher authorization: verify teacher belongs to all selected classrooms
        if (!$isAdmin) {
            foreach ($classroomIds as $cid) {
                if (!$this->isTeacherInClassroom($cid, $creatorId)) {
                    throw new Exception("You are not authorized to distribute resources to classroom ID: {$cid}.");
                }
            }
        }

        // External URL validation if provided or required
        if (($resourceType === 'link' || ($resourceType === 'video' && empty($file['name']))) && !empty($externalUrl)) {
            if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid external or video URL format.");
            }
        }

        if ($resourceType === 'link' && empty($externalUrl)) {
            throw new Exception("Please provide a valid web URL for link resource.");
        }

        if ($resourceType === 'quiz' && empty($quizId)) {
            throw new Exception("Please select an existing quiz to link.");
        }

        // 2. Handle File Upload if present
        $uploadedFileMeta = null;
        if (!empty($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedFileMeta = $this->handleFileUpload(
                $file, 
                __DIR__ . '/../../storage/uploads/classroom_resources/',
                self::ALLOWED_RESOURCE_EXTENSIONS,
                self::MAX_RESOURCE_FILE_SIZE
            );
        }

        // If document type and no file, require either file or link
        if ($resourceType === 'document' && empty($uploadedFileMeta) && empty($externalUrl)) {
            throw new Exception("Please attach a document file or provide a cloud document URL.");
        }

        // 3. Persist and Distribute Atomically
        $this->db->beginTransaction();
        try {
            $resourceData = [
                'title'           => $title,
                'description'     => $description,
                'resource_type'   => $resourceType,
                'file_path'       => $uploadedFileMeta['file_path'] ?? null,
                'original_name'   => $uploadedFileMeta['original_name'] ?? null,
                'file_size'       => $uploadedFileMeta['file_size'] ?? 0,
                'mime_type'       => $uploadedFileMeta['mime_type'] ?? null,
                'external_url'    => $externalUrl ?: null,
                'quiz_id'         => $quizId,
                'due_date'        => $dueDate,
                'max_points'      => $maxPoints,
                'target_audience' => $targetAudience,
                'status'          => $status,
                'created_by'      => $creatorId,
            ];

            $resourceId = $this->repository->create($resourceData);
            if ($resourceId <= 0) {
                throw new Exception("Failed to insert classroom resource record.");
            }

            // Bulk distribute to all selected classrooms
            $this->repository->distributeToClassrooms($resourceId, $classroomIds);

            $this->db->commit();
            return $resourceId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            // Delete orphan uploaded file if database insert failed
            if (!empty($uploadedFileMeta['absolute_path']) && file_exists($uploadedFileMeta['absolute_path'])) {
                @unlink($uploadedFileMeta['absolute_path']);
            }
            throw new Exception("Distribution failed: " . $e->getMessage());
        }
    }

    /**
     * Update an existing resource.
     */
    public function updateResource(int $id, array $data, ?array $file, int $userId, bool $isAdmin = false): bool
    {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Resource not found.");
        }

        if (!$isAdmin && (int)$existing['created_by'] !== $userId) {
            throw new Exception("You are not authorized to edit this resource.");
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $resourceType = strtolower(trim($data['resource_type'] ?? $existing['resource_type']));
        $targetAudience = strtolower(trim($data['target_audience'] ?? $existing['target_audience']));
        $externalUrl = trim($data['external_url'] ?? '');
        $quizId = !empty($data['quiz_id']) ? (int) $data['quiz_id'] : null;
        $dueDate = !empty($data['due_date']) ? trim($data['due_date']) : null;
        $maxPoints = isset($data['max_points']) && $data['max_points'] !== '' ? (float) $data['max_points'] : null;
        $status = ($data['status'] ?? $existing['status']) === 'draft' ? 'draft' : 'published';

        if (empty($title)) {
            throw new Exception("Resource title is required.");
        }

        // Classroom target validation
        $rawClassroomIds = $data['classroom_ids'] ?? [];
        if (!is_array($rawClassroomIds) || empty($rawClassroomIds)) {
            throw new Exception("Please select at least one Group Classroom.");
        }
        $classroomIds = array_values(array_unique(array_filter(array_map('intval', $rawClassroomIds))));

        // Handle replacement file if uploaded
        $uploadedFileMeta = null;
        if (!empty($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedFileMeta = $this->handleFileUpload(
                $file, 
                __DIR__ . '/../../storage/uploads/classroom_resources/',
                self::ALLOWED_RESOURCE_EXTENSIONS,
                self::MAX_RESOURCE_FILE_SIZE
            );
        }

        $this->db->beginTransaction();
        try {
            $updateData = [
                'title'           => $title,
                'description'     => $description,
                'resource_type'   => $resourceType,
                'external_url'    => $externalUrl ?: null,
                'quiz_id'         => $quizId,
                'due_date'        => $dueDate,
                'max_points'      => $maxPoints,
                'target_audience' => $targetAudience,
                'status'          => $status,
            ];

            if ($uploadedFileMeta) {
                $updateData['file_path']     = $uploadedFileMeta['file_path'];
                $updateData['original_name'] = $uploadedFileMeta['original_name'];
                $updateData['file_size']     = $uploadedFileMeta['file_size'];
                $updateData['mime_type']     = $uploadedFileMeta['mime_type'];
            }

            $this->repository->update($id, $updateData);
            $this->repository->syncDistributions($id, $classroomIds);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            if (!empty($uploadedFileMeta['absolute_path']) && file_exists($uploadedFileMeta['absolute_path'])) {
                @unlink($uploadedFileMeta['absolute_path']);
            }
            throw new Exception("Update failed: " . $e->getMessage());
        }
    }

    /**
     * Soft delete a resource.
     */
    public function deleteResource(int $id, int $userId, bool $isAdmin = false): bool
    {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Resource not found.");
        }

        if (!$isAdmin && (int)$existing['created_by'] !== $userId) {
            throw new Exception("You are not authorized to delete this resource.");
        }

        return $this->repository->softDelete($id);
    }

    /**
     * Process student assignment submission.
     */
    public function submitAssignment(int $resourceId, int $studentId, array $post, ?array $file): int
    {
        $resource = $this->repository->findById($resourceId);
        if (!$resource || $resource['resource_type'] !== 'assignment') {
            throw new Exception("Assignment resource not found.");
        }

        // Check if student belongs to any distributed classroom
        $studentRole = defined('ROLE_STUDENT') ? ROLE_STUDENT : 4;
        if (!$this->repository->canUserAccessResource($resourceId, $studentId, $studentRole)) {
            throw new Exception("You are not enrolled in the classroom for this assignment.");
        }

        // Check deadline
        if (!empty($resource['due_date'])) {
            $dueTimestamp = strtotime($resource['due_date']);
            if ($dueTimestamp && time() > $dueTimestamp) {
                throw new Exception("The submission deadline for this assignment has expired (" . date('d M Y, h:i A', $dueTimestamp) . ").");
            }
        }

        $notes = trim($post['notes'] ?? '');
        $uploadedFileMeta = null;

        if (!empty($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedFileMeta = $this->handleFileUpload(
                $file,
                __DIR__ . '/../../storage/uploads/assignment_submissions/',
                ['pdf', 'doc', 'docx', 'txt', 'zip', 'jpg', 'jpeg', 'png'],
                self::MAX_SUBMISSION_FILE_SIZE
            );
        }

        if (empty($notes) && empty($uploadedFileMeta)) {
            throw new Exception("Please enter your answer notes or attach a submission file.");
        }

        $submissionData = [
            'resource_id'   => $resourceId,
            'student_id'    => $studentId,
            'notes'         => $notes,
            'file_path'     => $uploadedFileMeta['file_path'] ?? null,
            'original_name' => $uploadedFileMeta['original_name'] ?? null,
        ];

        return $this->repository->saveAssignmentSubmission($submissionData);
    }

    /**
     * Grade student submission.
     */
    public function gradeSubmission(int $submissionId, float $points, ?string $feedback, int $gradedBy): bool
    {
        if ($points < 0) {
            throw new Exception("Points cannot be negative.");
        }

        return $this->repository->gradeSubmission($submissionId, $points, $feedback, $gradedBy);
    }

    /**
     * Get accessible active classrooms for a user based on role.
     */
    public function getEligibleClassrooms(int $userId, bool $isAdmin = false): array
    {
        if ($isAdmin) {
            $stmt = $this->db->query("
                SELECT c.id, c.class_name, c.class_title, c.class_code,
                       (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.status = 'active') as student_count,
                       (SELECT COUNT(*) FROM classroom_teachers ct WHERE ct.classroom_id = c.id) as teacher_count
                FROM classrooms c 
                WHERE c.status = 'Active' 
                ORDER BY c.class_name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // For Teacher
        $stmt = $this->db->prepare("
            SELECT c.id, c.class_name, c.class_title, c.class_code,
                   (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.status = 'active') as student_count,
                   (SELECT COUNT(*) FROM classroom_teachers ct2 WHERE ct2.classroom_id = c.id) as teacher_count
            FROM classrooms c
            INNER JOIN classroom_teachers ct ON c.id = ct.classroom_id
            WHERE ct.user_id = :uid AND c.status = 'Active'
            ORDER BY c.class_name ASC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get active quizzes list for dropdown.
     */
    public function getQuizzesList(): array
    {
        $stmt = $this->db->query("SELECT id, title, type, duration_minutes, total_marks FROM quizzes WHERE status = 'active' ORDER BY title ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Helper to check if teacher belongs to classroom.
     */
    public function isTeacherInClassroom(int $classroomId, int $teacherId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM classroom_teachers WHERE classroom_id = :cid AND user_id = :uid LIMIT 1");
        $stmt->execute([':cid' => $classroomId, ':uid' => $teacherId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Internal secure file upload handler.
     */
    private function handleFileUpload(array $file, string $targetDir, array $allowedExtensions, int $maxBytes): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $file['error']);
        }

        $fileSize = (int) $file['size'];
        if ($fileSize > $maxBytes) {
            $mbLimit = (int) ($maxBytes / (1024 * 1024));
            throw new Exception("File size exceeds maximum allowed limit of {$mbLimit}MB.");
        }

        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            throw new Exception("File extension '{$ext}' is not permitted.");
        }

        // Validate MIME type with finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Security check: never allow php executable types
        $disallowedMimes = [
            'application/x-httpd-php', 'application/x-php', 'text/x-php', 
            'application/javascript', 'application/x-msdownload', 'application/x-sh'
        ];
        if (in_array(strtolower((string)$mimeType), $disallowedMimes, true)) {
            throw new Exception("Executable files are strictly forbidden.");
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $safeFileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $targetDir . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to move uploaded file to secure storage.");
        }

        return [
            'file_path'     => $safeFileName,
            'original_name' => $originalName,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
            'absolute_path' => $destination,
        ];
    }
}
