<?php
require_once __DIR__ . '/../config/security.php';

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new \Exception("Invalid Security Token.");
    }
    // Rotate token after successful use
    unset($_SESSION['csrf_token']);
}
