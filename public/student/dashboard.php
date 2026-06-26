<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';

requireRole(ROLE_STUDENT);

// Fetch active notices for students
$stmt = $db->prepare("SELECT title, content, created_at FROM notices WHERE status = 'active' AND target_audience IN ('student', 'both') ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render view
require __DIR__ . '/../../views/student/dashboard.php';
