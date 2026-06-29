<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

// Requires logged in user, but not necessarily admin
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

use App\Controllers\Session\SessionJoinController;

$sessionId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$controller = new SessionJoinController($db, $sessionRepo, $meetingRepo, $attendanceService);
$controller->handleJoin($sessionId);
