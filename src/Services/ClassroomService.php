<?php

namespace App\Services;

use App\Repositories\ClassroomRepository;
use App\Repositories\ClassObserverRepository;
use App\Repositories\UserRepository;
use Exception;
use PDO;

class ClassroomService
{
    private ClassroomRepository $repository;
    private ?UserRepository $userRepository;
    private ?ClassObserverRepository $observerRepo;
    private ?PDO $db;

    public function __construct(
        ClassroomRepository $repository, 
        ?UserRepository $userRepository = null,
        ?ClassObserverRepository $observerRepo = null,
        ?PDO $db = null
    ) {
        $this->repository     = $repository;
        $this->db             = $db ?? ($GLOBALS['db'] ?? null);
        $this->userRepository = $userRepository ?? ($this->db ? new UserRepository($this->db) : null);
        $this->observerRepo   = $observerRepo ?? ($this->db ? new ClassObserverRepository($this->db) : null);
    }

    private function getObserverRepo(): ClassObserverRepository
    {
        if ($this->observerRepo === null) {
            if ($this->db !== null) {
                $this->observerRepo = new ClassObserverRepository($this->db);
            } else {
                throw new \LogicException('ClassObserverRepository is required for observer operations.');
            }
        }
        return $this->observerRepo;
    }

    public function createClassroom(array $data, int $adminId): bool
    {
        // 1. Generate unique class code if not provided
        $classCode = $data['class_code'] ?? $this->generateUniqueClassCode();

        // 2. Validate basic rules
        if (empty($data['class_name']) || empty($data['class_title'])) {
            throw new Exception("Class Name and Title are required.");
        }

        // 3. Resolve Teacher IDs (array or single integer fallback)
        $teacherIds = [];
        if (!empty($data['teacher_ids']) && is_array($data['teacher_ids'])) {
            $teacherIds = array_values(array_unique(array_filter(array_map('intval', $data['teacher_ids']), fn($id) => $id > 0)));
        } elseif (!empty($data['teacher_id'])) {
            $teacherIds = [(int) $data['teacher_id']];
        }

        if (empty($teacherIds)) {
            throw new Exception("At least one Teacher assignment is required.");
        }

        // 4. Resolve Student IDs (array or single integer fallback)
        $studentIds = [];
        if (!empty($data['student_ids']) && is_array($data['student_ids'])) {
            $studentIds = array_values(array_unique(array_filter(array_map('intval', $data['student_ids']), fn($id) => $id > 0)));
        } elseif (!empty($data['student_id'])) {
            $studentIds = [(int) $data['student_id']];
        }

        if (empty($studentIds)) {
            throw new Exception("At least one Student assignment is required.");
        }

        // 5. Determine Primary Teacher
        $primaryTeacherId = !empty($data['primary_teacher_id']) ? (int) $data['primary_teacher_id'] : null;
        if ($primaryTeacherId === null || !in_array($primaryTeacherId, $teacherIds, true)) {
            $primaryTeacherId = $teacherIds[0];
        }

        // 6. Validate Roles (ensure assigned teachers have ROLE_TEACHER and students have ROLE_STUDENT)
        $this->validateMemberRoles($teacherIds, $studentIds);

        $classroomData = [
            'class_code'  => $classCode,
            'class_name'  => trim($data['class_name']),
            'class_title' => trim($data['class_title']),
            'teacher_id'  => $primaryTeacherId,
            'student_id'  => $studentIds[0] ?? null,
            'status'      => in_array($data['status'] ?? 'Active', ['Active', 'Inactive']) ? $data['status'] : 'Active',
            'created_by'  => $adminId
        ];

        // 7. Atomic Transaction Execution
        $isOwnTx = false;
        if ($this->db && !$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnTx = true;
        }

        try {
            $created = $this->repository->create($classroomData);
            if (!$created) {
                throw new Exception("Failed to insert classroom record.");
            }

            $newClass = $this->repository->findByClassCode($classCode);
            if (!$newClass || empty($newClass['id'])) {
                throw new Exception("Failed to retrieve created classroom ID.");
            }
            $classroomId = (int) $newClass['id'];

            // Sync Teachers to classroom_teachers
            $this->repository->syncTeachers($classroomId, $teacherIds, $primaryTeacherId);

            // Sync Students to classroom_students
            $this->repository->syncStudents($classroomId, $studentIds);

            // Sync Observers if supplied
            if (!empty($data['observer_ids']) && is_array($data['observer_ids'])) {
                $this->getObserverRepo()->assignClassroomObservers($classroomId, $data['observer_ids'], $adminId);
            }

            if ($isOwnTx && $this->db) {
                $this->db->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if ($isOwnTx && $this->db && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateClassroom(int $id, array $data, ?int $actorId = null): bool
    {
        if (empty($data['class_name']) || empty($data['class_title'])) {
            throw new Exception("Class Name and Title are required.");
        }

        $classroomData = [
            'class_name'  => trim($data['class_name']),
            'class_title' => trim($data['class_title']),
            'status'      => in_array($data['status'] ?? 'Active', ['Active', 'Inactive']) ? $data['status'] : 'Active'
        ];

        $isOwnTx = false;
        if ($this->db && !$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnTx = true;
        }

        try {
            $updated = $this->repository->update($id, $classroomData);
            if (!$updated) {
                throw new Exception("Failed to update classroom details.");
            }

            // Sync teachers if teacher_ids or teacher_id provided
            if (isset($data['teacher_ids']) || isset($data['teacher_id'])) {
                $teacherIds = [];
                if (!empty($data['teacher_ids']) && is_array($data['teacher_ids'])) {
                    $teacherIds = array_values(array_unique(array_filter(array_map('intval', $data['teacher_ids']), fn($tid) => $tid > 0)));
                } elseif (!empty($data['teacher_id'])) {
                    $teacherIds = [(int) $data['teacher_id']];
                }

                if (empty($teacherIds)) {
                    throw new Exception("A classroom must have at least one assigned teacher.");
                }

                $primaryTeacherId = !empty($data['primary_teacher_id']) ? (int) $data['primary_teacher_id'] : null;
                if ($primaryTeacherId === null || !in_array($primaryTeacherId, $teacherIds, true)) {
                    $primaryTeacherId = $teacherIds[0];
                }

                $this->repository->syncTeachers($id, $teacherIds, $primaryTeacherId);
            }

            // Sync students if student_ids or student_id provided
            if (isset($data['student_ids']) || isset($data['student_id'])) {
                $studentIds = [];
                if (!empty($data['student_ids']) && is_array($data['student_ids'])) {
                    $studentIds = array_values(array_unique(array_filter(array_map('intval', $data['student_ids']), fn($sid) => $sid > 0)));
                } elseif (!empty($data['student_id'])) {
                    $studentIds = [(int) $data['student_id']];
                }

                if (empty($studentIds)) {
                    throw new Exception("A classroom must have at least one assigned student.");
                }

                $this->repository->syncStudents($id, $studentIds);
            }

            // Synchronize observer assignments if observer_ids field is present in request
            if (isset($data['observer_ids'])) {
                $observerIds = is_array($data['observer_ids']) ? $data['observer_ids'] : [];
                $this->getObserverRepo()->assignClassroomObservers($id, $observerIds, $actorId ?? 1);
            }

            if ($isOwnTx && $this->db) {
                $this->db->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if ($isOwnTx && $this->db && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function deleteClassroom(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getClassroomDetails(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getAssignedTeacherIds(int $id): array
    {
        return $this->repository->getAssignedTeacherIds($id);
    }

    public function getAssignedStudentIds(int $id): array
    {
        return $this->repository->getAssignedStudentIds($id);
    }

    public function isTeacherInClassroom(int $classroomId, int $teacherId): bool
    {
        return $this->repository->isTeacherInClassroom($classroomId, $teacherId);
    }

    public function isStudentInClassroom(int $classroomId, int $studentId): bool
    {
        return $this->repository->isStudentInClassroom($classroomId, $studentId);
    }

    public function getClassroomObservers(int $id): array
    {
        return $this->getObserverRepo()->getClassroomObservers($id);
    }

    public function getClassroomObserverIds(int $id): array
    {
        return $this->getObserverRepo()->getClassroomObserverIds($id);
    }

    public function getEligibleObservers(): array
    {
        return $this->getObserverRepo()->getEligibleObservers();
    }

    public function getPaginatedClassrooms(
        int $page, 
        int $limit, 
        string $search = '', 
        string $sort = 'created_at', 
        string $order = 'DESC',
        ?array $allowedClassroomIds = null
    ): array {
        $offset = ($page - 1) * $limit;
        $classrooms = $this->repository->getPaginatedClassrooms($limit, $offset, $search, $sort, $order, $allowedClassroomIds);
        $total = $this->repository->countTotalClassrooms($search, $allowedClassroomIds);

        return [
            'data' => $classrooms,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function getTeacherClassrooms(int $teacherId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $classrooms = $this->repository->getTeacherClassrooms($teacherId, $limit, $offset);
        $total = $this->repository->countTeacherClassrooms($teacherId);

        return [
            'data' => $classrooms,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function getStudentClassrooms(int $studentId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $classrooms = $this->repository->getStudentClassrooms($studentId, $limit, $offset);
        $total = $this->repository->countStudentClassrooms($studentId);

        return [
            'data' => $classrooms,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    private function generateUniqueClassCode(): string
    {
        $prefix = 'CLASSROOM-';
        
        do {
            $randomString = strtoupper(bin2hex(random_bytes(3)));
            $code = $prefix . $randomString;
        } while (!$this->repository->isClassCodeUnique($code));
        
        return $code;
    }

    /**
     * Get active users by role bitmask via UserRepository.
     * UserRepository must be injected to use this method.
     */
    public function getUsersByRole(int $roleMask): array
    {
        if ($this->userRepository === null) {
            if ($this->db !== null) {
                $this->userRepository = new \App\Repositories\UserRepository($this->db);
            } else {
                throw new \LogicException('UserRepository is required to call getUsersByRole().');
            }
        }
        return $this->userRepository->getUsersByRole($roleMask);
    }

    /**
     * Validates that all IDs in $teacherIds have ROLE_TEACHER and $studentIds have ROLE_STUDENT
     */
    private function validateMemberRoles(array $teacherIds, array $studentIds): void
    {
        if (!$this->db) {
            return;
        }

        // Validate Teachers
        if (!empty($teacherIds)) {
            $inClause = implode(',', array_map('intval', $teacherIds));
            $stmt = $this->db->query("SELECT id, roles_mask FROM users WHERE id IN ($inClause)");
            $teachers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            foreach ($teacherIds as $tid) {
                if (!isset($teachers[$tid]) || !((int)$teachers[$tid] & ROLE_TEACHER)) {
                    throw new Exception("User ID {$tid} is not a valid registered Teacher.");
                }
            }
        }

        // Validate Students
        if (!empty($studentIds)) {
            $inClause = implode(',', array_map('intval', $studentIds));
            $stmt = $this->db->query("SELECT id, roles_mask FROM users WHERE id IN ($inClause)");
            $students = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            foreach ($studentIds as $sid) {
                if (!isset($students[$sid]) || !((int)$students[$sid] & ROLE_STUDENT)) {
                    throw new Exception("User ID {$sid} is not a valid registered Student.");
                }
            }
        }
    }
}
