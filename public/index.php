<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

$authService = new \App\Services\AuthService($auth);

// Redirect to dashboard if already logged in
redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        $authService->login($_POST);
        
        redirectIfLoggedIn();
        exit();
        
    } catch (\Exception $e) {
        if ($e->getMessage() === 'Invalid Security Token. Please refresh and try again.') {
            $_SESSION['login_error'] = $e->getMessage();
        } else {
            $_SESSION['login_error'] = $e->getMessage();
            $_SESSION['old_email'] = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        }
    }
    
    // If login failed, redirect via GET (PRG Pattern)
    header("Location: index.php");
    exit();
}

// Retrieve flash messages for GET requests
$error = $_SESSION['login_error'] ?? '';
$oldEmail = $_SESSION['old_email'] ?? '';

// Clear the session flash messages so they don't persist
unset($_SESSION['login_error']);
unset($_SESSION['old_email']);

// Render view
require __DIR__ . '/../views/login.php';
