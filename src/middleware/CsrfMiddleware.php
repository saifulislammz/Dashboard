<?php
require_once __DIR__ . '/../config/security.php';

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        throw new \Exception("Your session has expired due to inactivity. Please refresh the page and try again.");
    }
    if (empty($token)) {
        throw new \Exception("Invalid Security Token: Provided token is empty.");
    }
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        throw new \Exception("Invalid Security Token: Token mismatch.");
    }
    // Token rotation is removed to support multiple tabs and back button.
    // The token remains valid for the duration of the user's session.
}
