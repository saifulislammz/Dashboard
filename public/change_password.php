<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';

requireLogin();

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new \Exception("All fields are required.");
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new \Exception("New password and confirm password do not match.");
        }
        
        // Password strength validation
        $uppercase = preg_match('@[A-Z]@', $newPassword);
        $lowercase = preg_match('@[a-z]@', $newPassword);
        $number    = preg_match('@[0-9]@', $newPassword);
        $special   = preg_match('@[^\w]@', $newPassword);

        if (!$uppercase || !$lowercase || !$number || !$special || strlen($newPassword) < 8) {
            throw new \Exception("New password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.");
        }
        
        // Update password using Delight Auth
        $auth->changePassword($currentPassword, $newPassword);
        
        session_regenerate_id(true);
        $successMessage = "Password updated successfully.";
        
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $errorMessage = "Current password is incorrect.";
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $errorMessage = "Too many requests. Please try again later.";
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

require __DIR__ . '/../views/change_password.php';
