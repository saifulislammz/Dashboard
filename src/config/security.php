<?php

// Strictly enforce secure sessions
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    // ini_set('session.cookie_secure', 1); // Uncomment if using HTTPS
    session_start();
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(self), camera=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; img-src 'self' data: https://images.unsplash.com; font-src 'self' data: https://fonts.gstatic.com https://unpkg.com; media-src 'self' blob:;");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

// XSS Protection for older browsers (though CSP covers this)
header("X-XSS-Protection: 1; mode=block");

// Output escaping function
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
