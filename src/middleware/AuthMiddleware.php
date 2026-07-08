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
        echo "<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f8f9fa; color: #212529; }
        .container { text-align: center; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; font-size: 2.5rem; margin-bottom: 0.5rem; }
        p { font-size: 1.1rem; margin-bottom: 1.5rem; color: #6c757d; }
        a { display: inline-block; padding: 0.5rem 1rem; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; }
        a:hover { background-color: #0b5ed7; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>403 Forbidden</h1>
        <p>You do not have permission to access this page.</p>
        <a href='javascript:history.back()'>Go Back</a>
    </div>
</body>
</html>";
        exit();
    }
}
