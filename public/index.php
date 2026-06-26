<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';

// Redirect to dashboard if already logged in
redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        validateCsrfToken($_POST['csrf_token'] ?? '');
    } catch (\Exception $e) {
        $_SESSION['login_error'] = 'Invalid Security Token. Please refresh and try again.';
        header("Location: index.php");
        exit();
    }

    // 100% Sanitization and Validation
    $emailInput = $_POST['email'] ?? '';
    // Strip all tags completely to block XSS payloads at the root
    $emailInput = trim($emailInput);
    $email = filter_var($emailInput, FILTER_SANITIZE_EMAIL);
    
    $password = $_POST['password'] ?? '';
    $rememberDuration = !empty($_POST['remember_me']) ? (int) (60 * 60 * 24 * 30) : null; // 30 days

    // Strictly enforce email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = 'Please enter a valid email address.';
        $_SESSION['old_email'] = $email;
        header("Location: index.php");
        exit();
    }

    // Sanitize and limit password length (Prevents DoS from huge inputs)
    if (strlen($password) < 1 || strlen($password) > 255) {
        $_SESSION['login_error'] = 'Invalid password length.';
        $_SESSION['old_email'] = $email;
        header("Location: index.php");
        exit();
    }

    try {
        $auth->login($email, $password, $rememberDuration);
        
        // Anti Session Fixation
        session_regenerate_id(true);
        
        redirectIfLoggedIn();
        exit();
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $_SESSION['login_error'] = 'Wrong email address or password'; // Obfuscate exact error
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $_SESSION['login_error'] = 'Wrong email address or password';
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $_SESSION['login_error'] = 'Email not verified';
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $_SESSION['login_error'] = 'Too many requests. Please try again later.';
    } catch (\Exception $e) {
        $_SESSION['login_error'] = 'An unexpected error occurred.';
    }
    
    // If login failed, store old email and redirect via GET (PRG Pattern)
    $_SESSION['old_email'] = $email;
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
