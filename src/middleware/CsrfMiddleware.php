<?php
require_once __DIR__ . '/../config/security.php';

/**
 * Maximum number of tokens retained in the pool to support multiple tabs
 * and the browser back-button without CSRF errors.
 */
const CSRF_TOKEN_POOL_SIZE = 10;

/**
 * Rotates the CSRF token, pushing the new token into the session pool.
 */
function rotateCsrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    
    $newToken = bin2hex(random_bytes(32));
    
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
        if (!empty($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_tokens'][] = $_SESSION['csrf_token'];
        }
    }
    
    // Append new token to the pool
    $_SESSION['csrf_tokens'][] = $newToken;
    
    // Prune the pool if it exceeds the maximum size
    while (count($_SESSION['csrf_tokens']) > CSRF_TOKEN_POOL_SIZE) {
        array_shift($_SESSION['csrf_tokens']);
    }
    
    $_SESSION['csrf_token'] = $newToken;
    return $newToken;
}

/**
 * Generates or retrieves the active CSRF token.
 */
function generateCsrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return rotateCsrfToken();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validates the provided CSRF token against active session tokens and pool.
 * Rotates the token upon successful validation by default.
 *
 * @throws \Exception On missing or mismatched token
 */
function validateCsrfToken($token, bool $rotate = true): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if (empty($_SESSION['csrf_token']) && empty($_SESSION['csrf_tokens'])) {
        throw new \Exception("Your session has expired due to inactivity. Please refresh the page and try again.");
    }

    if (empty($token) || !is_string($token)) {
        throw new \Exception("Invalid Security Token: Provided token is empty.");
    }

    // Gather all valid tokens (primary token + pool tokens)
    $validTokens = [];
    if (!empty($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])) {
        $validTokens[] = $_SESSION['csrf_token'];
    }
    if (!empty($_SESSION['csrf_tokens']) && is_array($_SESSION['csrf_tokens'])) {
        foreach ($_SESSION['csrf_tokens'] as $t) {
            if (is_string($t) && !in_array($t, $validTokens, true)) {
                $validTokens[] = $t;
            }
        }
    }

    // Timing-attack safe comparison
    $matched = false;
    foreach ($validTokens as $validToken) {
        if (hash_equals($validToken, $token)) {
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        throw new \Exception("Invalid Security Token: Token mismatch.");
    }

    // Token rotation with multi-tab support
    if ($rotate) {
        rotateCsrfToken();
    }

    return true;
}
