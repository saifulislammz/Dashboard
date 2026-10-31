<?php

declare(strict_types=1);

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../Utils/PermissionCache.php';

use App\Utils\PermissionCache;

/**
 * Check if the currently logged-in user is a Super Administrator.
 *
 * @return bool
 */
function isSuperAdmin(): bool
{
    global $auth, $db;
    if (!$auth || !$auth->isLoggedIn()) {
        return false;
    }

    $userId = (int) $auth->getUserId();

    // Primary developer / super admin user ID or Delight Auth SUPER_ADMIN bitmask
    if ($userId === 1 || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)) {
        return true;
    }

    // Check custom role table if role slug is 'super_admin'
    try {
        if (isset($db) && $db instanceof \PDO) {
            $roleRepo = new \App\Repositories\RoleRepository($db);
            $role = $roleRepo->getUserPrimaryRole($userId);
            if ($role && $role['slug'] === 'super_admin') {
                return true;
            }
        }
    } catch (\Throwable $e) {
        // Fallback
    }

    return false;
}

/**
 * Check if the logged-in user possesses a specific permission.
 *
 * @param string $permissionSlug
 * @return bool
 */
function hasPermission(string $permissionSlug): bool
{
    global $auth, $db;
    if (!$auth || !$auth->isLoggedIn()) {
        return false;
    }

    if (isSuperAdmin()) {
        return true;
    }

    if (!isset($db) || !($db instanceof \PDO)) {
        return false;
    }

    $userId = (int) $auth->getUserId();
    return PermissionCache::hasPermission($userId, $permissionSlug, $db, false);
}

/**
 * Check if the logged-in user has ANY of the specified permissions.
 *
 * @param string[] $permissionSlugs
 * @return bool
 */
function hasAnyPermission(array $permissionSlugs): bool
{
    global $auth, $db;
    if (!$auth || !$auth->isLoggedIn()) {
        return false;
    }

    if (isSuperAdmin()) {
        return true;
    }

    if (!isset($db) || !($db instanceof \PDO)) {
        return false;
    }

    $userId = (int) $auth->getUserId();
    return PermissionCache::hasAnyPermission($userId, $permissionSlugs, $db, false);
}

/**
 * Enforce that the user has a specific permission. Denies access with 403 Forbidden otherwise.
 *
 * @param string $permissionSlug
 * @return void
 */
function requirePermission(string $permissionSlug): void
{
    requireLogin();

    if (!hasPermission($permissionSlug)) {
        renderForbiddenResponse("You do not have the required permission ('{$permissionSlug}') to access this resource.");
    }
}

/**
 * Enforce that the user has at least ONE of the specified permissions.
 *
 * @param string[] $permissionSlugs
 * @return void
 */
function requireAnyPermission(array $permissionSlugs): void
{
    requireLogin();

    if (!hasAnyPermission($permissionSlugs)) {
        renderForbiddenResponse("You do not have permission to access this section.");
    }
}

/**
 * Enforce that the logged-in user must be a Super Administrator.
 *
 * @return void
 */
function requireSuperAdmin(): void
{
    requireLogin();

    if (!isSuperAdmin()) {
        renderForbiddenResponse("This area is restricted exclusively to Super Administrators.");
    }
}

/**
 * Render a branded 403 Forbidden page or JSON response.
 *
 * @param string $message
 * @return void
 */
function renderForbiddenResponse(string $message): void
{
    http_response_code(403);

    // If API / AJAX request, send clean JSON
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'status' => 403,
            'error' => 'Forbidden',
            'message' => $message
        ]);
        exit();
    }

    // Render HTML Forbidden Page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 - Access Forbidden</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 antialiased font-sans">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-8 text-center">
            <div class="w-16 h-16 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm border border-red-100">
                <i class="ph ph-shield-warning text-3xl"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight mb-2">Access Restricted</h1>
            <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <div class="bg-amber-50 rounded-xl p-3 mb-6 border border-amber-100 text-left flex items-start gap-3">
                <i class="ph ph-info text-amber-600 text-lg flex-shrink-0 mt-0.5"></i>
                <div class="text-xs text-amber-800">
                    If you believe you should have access to this section, please contact your <strong>Super Administrator</strong> to update your assigned role.
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="javascript:history.back()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition-colors">
                    <i class="ph ph-arrow-left"></i> Go Back
                </a>
                <a href="/admin/dashboard.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors">
                    <i class="ph ph-house"></i> Dashboard
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

