<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        try {
            $auth->logOutEverywhere();
        } catch (\Delight\Auth\NotLoggedInException $e) {
            // Not logged in anyway
        }
        
        // Destroy session data
        $auth->destroySession();
    } catch (\Exception $e) {
        // Invalid CSRF token, ignore logout or handle error
    }
}

header("Location: index.php");
exit();
