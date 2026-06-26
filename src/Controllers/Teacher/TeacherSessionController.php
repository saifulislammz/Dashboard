<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Services\Sessions\ClassSessionService;
use App\Repositories\ClassroomRepository;
use PDO;

class TeacherSessionController
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
        $teacherId = $auth->getUserId();

        // Check if viewing a specific classroom or all upcoming sessions
        $classroomId = isset($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : 0;
        
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        if ($classroomId > 0) {
            // Verify teacher has access to this classroom
            $classroom = $this->classroomRepo->findById($classroomId);
            if (!$classroom || $classroom['teacher_id'] !== $teacherId) {
                http_response_code(403);
                die('<h1>Access denied.</h1>');
            }
            
            $stmt = $this->db->prepare("
                SELECT cs.*, sm.join_url, sm.start_url, sm.meet_link, sm.passcode, sm.generation_status, c.class_name 
                FROM class_sessions cs 
                LEFT JOIN session_meetings sm ON sm.session_id = cs.id
                JOIN classrooms c ON c.id = cs.classroom_id
                WHERE cs.classroom_id = :cid
                ORDER BY cs.session_date DESC, cs.start_time DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':cid', $classroomId, PDO::PARAM_INT);
            
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM class_sessions WHERE classroom_id = :cid");
            $countStmt->bindValue(':cid', $classroomId, PDO::PARAM_INT);
            
            $pageTitle = "Live Sessions — " . $classroom['class_name'];
        } else {
            // All upcoming sessions for this teacher across all their classrooms
            $stmt = $this->db->prepare("
                SELECT cs.*, sm.join_url, sm.start_url, sm.meet_link, sm.passcode, sm.generation_status, c.class_name 
                FROM class_sessions cs 
                LEFT JOIN session_meetings sm ON sm.session_id = cs.id
                JOIN classrooms c ON c.id = cs.classroom_id
                WHERE c.teacher_id = :tid 
                  AND cs.session_date >= CURDATE()
                  AND cs.status NOT IN ('cancelled', 'completed')
                ORDER BY cs.session_date ASC, cs.start_time ASC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':tid', $teacherId, PDO::PARAM_INT);
            
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM class_sessions cs
                JOIN classrooms c ON c.id = cs.classroom_id
                WHERE c.teacher_id = :tid 
                  AND cs.session_date >= CURDATE()
                  AND cs.status NOT IN ('cancelled', 'completed')
            ");
            $countStmt->bindValue(':tid', $teacherId, PDO::PARAM_INT);
            
            $pageTitle = "My Upcoming Live Sessions";
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $pages = (int) ceil($total / $limit);

        $activeMenu = 'teacher_sessions';

        require __DIR__ . '/../../../views/teacher/sessions.php';
    }
}
