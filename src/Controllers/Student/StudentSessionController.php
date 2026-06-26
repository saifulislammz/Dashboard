<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Services\Sessions\ClassSessionService;
use App\Repositories\ClassroomRepository;
use PDO;

class StudentSessionController
{
    private PDO $db;
    private ClassSessionService $sessionService;
    private ClassroomRepository $classroomRepo;

    public function __construct(PDO $db, ClassSessionService $sessionService, ClassroomRepository $classroomRepo)
    {
        $this->db             = $db;
        $this->sessionService = $sessionService;
        $this->classroomRepo  = $classroomRepo;
    }

    public function index(): void
    {
        global $auth;
        $studentId = $auth->getUserId();

        $classroomId = isset($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : 0;
        
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        if ($classroomId > 0) {
            // Verify student is enrolled in this classroom
            $classroom = $this->classroomRepo->findById($classroomId);
            if (!$classroom || $classroom['student_id'] !== $studentId) {
                http_response_code(403);
                die('<h1>Access denied.</h1>');
            }
            
            // For student view, only show active or recently completed sessions.
            // Wait, maybe show all so they know the schedule? Yes, show all not cancelled.
            $stmt = $this->db->prepare("
                SELECT cs.*, sm.generation_status, c.class_name, u.username AS teacher_name
                FROM class_sessions cs 
                LEFT JOIN session_meetings sm ON sm.session_id = cs.id
                JOIN classrooms c ON c.id = cs.classroom_id
                LEFT JOIN users u ON u.id = c.teacher_id
                WHERE cs.classroom_id = :cid
                  AND cs.status != 'cancelled'
                ORDER BY cs.session_date DESC, cs.start_time DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':cid', $classroomId, PDO::PARAM_INT);
            
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM class_sessions 
                WHERE classroom_id = :cid AND status != 'cancelled'
            ");
            $countStmt->bindValue(':cid', $classroomId, PDO::PARAM_INT);
            
            $pageTitle = "Live Sessions — " . $classroom['class_name'];
        } else {
            // All upcoming sessions for this student across all their classrooms
            $stmt = $this->db->prepare("
                SELECT cs.*, sm.generation_status, c.class_name, u.username AS teacher_name
                FROM class_sessions cs 
                LEFT JOIN session_meetings sm ON sm.session_id = cs.id
                JOIN classrooms c ON c.id = cs.classroom_id
                LEFT JOIN users u ON u.id = c.teacher_id
                WHERE c.student_id = :sid 
                  AND cs.session_date >= CURDATE()
                  AND cs.status NOT IN ('cancelled', 'completed')
                ORDER BY cs.session_date ASC, cs.start_time ASC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':sid', $studentId, PDO::PARAM_INT);
            
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM class_sessions cs
                JOIN classrooms c ON c.id = cs.classroom_id
                WHERE c.student_id = :sid 
                  AND cs.session_date >= CURDATE()
                  AND cs.status NOT IN ('cancelled', 'completed')
            ");
            $countStmt->bindValue(':sid', $studentId, PDO::PARAM_INT);
            
            $pageTitle = "My Upcoming Live Classes";
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $pages = (int) ceil($total / $limit);

        // Fetch join open minutes to display helpful hints
        $stmtSet = $this->db->query("SELECT setting_val FROM meeting_settings WHERE setting_key = 'join_open_minutes_before'");
        $joinOpenMinutes = (int) ($stmtSet->fetchColumn() ?: 10);

        $activeMenu = 'student_sessions';

        require __DIR__ . '/../../../views/student/sessions.php';
    }
}
