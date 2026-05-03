<?php
/**
 * auth.php — Session guard for all protected admin pages.
 *
 * Include at the very top of every admin page (before any output).
 * Redirects unauthenticated requests to the login page and periodically
 * rotates the session ID to limit session-fixation exposure.
 */

declare(strict_types=1);

// Start session only if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie before starting
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),   // true on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Security response headers (safe to emit here before any output)
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

/**
 * Guard: require a valid, positive admin_id in the session.
 */
$adminId = $_SESSION['admin_id'] ?? null;

if (!is_int($adminId) || $adminId <= 0) {
    // Preserve the originally requested URL so login can redirect back
    $requestUri = filter_var($_SERVER['REQUEST_URI'] ?? '', FILTER_SANITIZE_URL);
    $redirect   = '/admin/login.php';
    if ($requestUri !== '' && $requestUri !== '/admin/login.php') {
        $redirect .= '?next=' . urlencode($requestUri);
    }

    header('Location: ' . $redirect);
    exit;
}

/**
 * Periodic session ID regeneration (every 30 minutes).
 * Limits the window of opportunity if a session token is stolen.
 */
$now             = time();
$lastRegenerated = (int) ($_SESSION['last_regenerated'] ?? 0);
$regenerateAfter = 1800; // 30 minutes in seconds

if ($now - $lastRegenerated >= $regenerateAfter) {
    session_regenerate_id(true); // true = delete old session file
    $_SESSION['last_regenerated'] = $now;
}
