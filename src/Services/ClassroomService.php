<?php

namespace App\Services;

use App\Repositories\ClassroomRepository;
use Exception;

class ClassroomService
{
    private ClassroomRepository $repository;

    public function __construct(ClassroomRepository $repository)
    {
        $this->repository = $repository;
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
            'class_name' => htmlspecialchars(trim($data['class_name'])),
            'class_title' => htmlspecialchars(trim($data['class_title'])),
            'teacher_id' => (int) $data['teacher_id'],
            'student_id' => (int) $data['student_id'],
            'status' => in_array($data['status'], ['Active', 'Inactive']) ? $data['status'] : 'Active',
            'created_by' => $adminId
        ];

        return $this->repository->create($classroomData);
    }

    public function updateClassroom(int $id, array $data): bool
    {
        if (empty($data['class_name']) || empty($data['class_title'])) {
            throw new Exception("Class Name and Title are required.");
        }

        $classroomData = [
            'class_name' => htmlspecialchars(trim($data['class_name'])),
            'class_title' => htmlspecialchars(trim($data['class_title'])),
            'status' => in_array($data['status'], ['Active', 'Inactive']) ? $data['status'] : 'Active'
        ];

        return $this->repository->update($id, $classroomData);
    }

    public function deleteClassroom(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getClassroomDetails(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getPaginatedClassrooms(int $page, int $limit, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        $classrooms = $this->repository->getPaginatedClassrooms($limit, $offset, $search);
        $total = $this->repository->countTotalClassrooms($search);

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
            $randomString = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $code = $prefix . $randomString;
        } while (!$this->repository->isClassCodeUnique($code));
        
        return $code;
    }
}
