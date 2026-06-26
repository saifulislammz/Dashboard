<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/roles.php';

function requireLogin() {
    global $auth;
    if (!$auth->isLoggedIn()) {
        header("Location: /index.php");
        exit();
    }
    
    // Check if account is active (Status 0 usually means normal in Delight Auth)
    // Actually Delight Auth checks status automatically on login.
    // We can also verify it here if needed, but standard is fine.
}

function redirectIfLoggedIn() {
    global $auth;
    if ($auth->isLoggedIn()) {
        if ($auth->hasRole(ROLE_ADMIN) || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)) {
            header("Location: /admin/dashboard.php");
        } elseif ($auth->hasRole(ROLE_TEACHER)) {
            header("Location: /teacher/dashboard.php");
        } elseif ($auth->hasRole(ROLE_STUDENT)) {
            header("Location: /student/dashboard.php");
        } else {
            // Fallback
            header("Location: /profile.php");
        }
        exit();
    }
}

function requireRole(int $role) {
    global $auth;
    requireLogin();
    
    if ($role === ROLE_ADMIN && $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)) {
        return; // Super admin also gets admin access
    }

    if (!$auth->hasRole($role)) {
        http_response_code(403);
        die("403 Forbidden - You do not have permission to access this page.");
    }
}
