<?php
/**
 * logout.php — Destroys the admin session and redirects to login.
 *
 * Only accepts POST requests (with a valid CSRF token) to prevent
 * logout CSRF attacks via image tags or iframes.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

// Harden session cookie before starting
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Only accept POST — silently redirect GETs back to the dashboard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/index.php');
}

// CSRF check: reject forged cross-site logout requests
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    // Treat an invalid CSRF the same as an unauth request — just redirect
    redirect('/admin/login.php');
}

// ── Tear down the session completely ─────────────────────────────────────────

// 1. Clear all session variables
session_unset();

// 2. Delete the session cookie from the client
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

// 3. Destroy the server-side session data
session_destroy();

redirect('/admin/login.php');
