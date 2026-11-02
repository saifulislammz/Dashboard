<?php

namespace App\Services;

use App\Repositories\ClassroomRepository;
use App\Repositories\ClassObserverRepository;
use App\Repositories\UserRepository;
use Exception;

class ClassroomService
{
    private ClassroomRepository $repository;
    private ?UserRepository $userRepository;
    private ?ClassObserverRepository $observerRepo;

    public function __construct(
        ClassroomRepository $repository, 
        ?UserRepository $userRepository = null,
        ?ClassObserverRepository $observerRepo = null
    ) {
        $this->repository     = $repository;
        $this->userRepository = $userRepository ?? (isset($GLOBALS['db']) ? new UserRepository($GLOBALS['db']) : null);
        $this->observerRepo   = $observerRepo ?? (isset($GLOBALS['db']) ? new ClassObserverRepository($GLOBALS['db']) : null);
    }

    private function getObserverRepo(): ClassObserverRepository
    {
        if ($this->observerRepo === null) {
            if (isset($GLOBALS['db'])) {
                $this->observerRepo = new ClassObserverRepository($GLOBALS['db']);
            } else {
                throw new \LogicException('ClassObserverRepository is required for observer operations.');
            }
        }
        return $this->observerRepo;
    }

    public function createClassroom(array $data, int $adminId): bool
    {
        // Generate unique class code if not provided
        $classCode = $data['class_code'] ?? $this->generateUniqueClassCode();

        // Validate basic rules
        if (empty($data['class_name']) || empty($data['class_title'])) {
            throw new Exception("Class Name and Title are required.");
        }

        if (empty($data['teacher_id']) || empty($data['student_id'])) {
            throw new Exception("Teacher and Student assignments are required.");
        }

        $classroomData = [
            'class_code' => $classCode,
            'class_name' => trim($data['class_name']),
            'class_title' => trim($data['class_title']),
            'teacher_id' => (int) $data['teacher_id'],
            'student_id' => (int) $data['student_id'],
            'status' => in_array($data['status'], ['Active', 'Inactive']) ? $data['status'] : 'Active',
            'created_by' => $adminId
        ];

        $created = $this->repository->create($classroomData);
        if (!$created) {
            return false;
        }

        // If observer IDs were supplied, assign them to the new classroom
        if (!empty($data['observer_ids']) && is_array($data['observer_ids'])) {
            $newClass = $this->repository->findByClassCode($classCode);
            if ($newClass && !empty($newClass['id'])) {
                $this->getObserverRepo()->assignClassroomObservers((int) $newClass['id'], $data['observer_ids'], $adminId);
            }
        }

        return true;
    }

    public function updateClassroom(int $id, array $data, ?int $actorId = null): bool
    {
        if (empty($data['class_name']) || empty($data['class_title'])) {
            throw new Exception("Class Name and Title are required.");
        }

        $classroomData = [
            'class_name' => trim($data['class_name']),
            'class_title' => trim($data['class_title']),
            'status' => in_array($data['status'], ['Active', 'Inactive']) ? $data['status'] : 'Active'
        ];

        $updated = $this->repository->update($id, $classroomData);
        if (!$updated) {
            return false;
        }

        // Synchronize observer assignments if observer_ids field is present in request
        if (isset($data['observer_ids'])) {
            $observerIds = is_array($data['observer_ids']) ? $data['observer_ids'] : [];
            $this->getObserverRepo()->assignClassroomObservers($id, $observerIds, $actorId ?? 1);
        }

        return true;
    }

    public function deleteClassroom(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getClassroomDetails(int $id): ?array
    {
        return $this->repository->findById($id);
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
            // Generate a random 6-character alphanumeric string to append
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
            if (isset($GLOBALS['db'])) {
                $this->userRepository = new \App\Repositories\UserRepository($GLOBALS['db']);
            } else {
                throw new \LogicException('UserRepository is required to call getUsersByRole().');
            }
        }
        return $this->userRepository->getUsersByRole($roleMask);
    }
}
