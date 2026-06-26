<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';

requireRole(ROLE_TEACHER);

// Fetch active notices for teachers
$stmt = $db->prepare("SELECT title, content, created_at FROM notices WHERE status = 'active' AND target_audience IN ('teacher', 'both') ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render view
require __DIR__ . '/../../views/teacher/dashboard.php';
