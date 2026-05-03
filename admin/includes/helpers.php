<?php
/**
 * Shared helper functions for the Maison 1815 admin dashboard.
 */

if (!defined('BASE_PATH')) {
    require_once dirname(__DIR__, 2) . '/config.php';
}

// ──────────────────────────────────────────────────────────────────────────────
// Slug
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Generate a URL-safe slug from $title, guaranteed unique in $table.slug.
 *
 * @param string $title     Source text (any language / accented chars OK)
 * @param PDO    $pdo       Active database connection
 * @param string $table     Target table that has a `slug` column (whitelisted)
 * @param int    $excludeId Row id to skip when checking uniqueness (edit mode)
 */
function generate_slug(string $title, PDO $pdo, string $table, int $excludeId = 0): string
{
    // Whitelist table names to prevent SQL injection via the table parameter
    $allowed = ['video_projects', 'photo_projects'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException("Table '$table' is not allowed for slug generation.");
    }

    // 1. Lowercase
    $slug = mb_strtolower(trim($title), 'UTF-8');

    // 2. Transliterate accented / special characters
    $map = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd',
        'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'œ' => 'oe',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'ß' => 'ss',
        'þ' => 'th',
        "'" => '-', "'" => '-', "'" => '-',
        '"' => '-', '"' => '-',
        '&' => 'and',
    ];
    $slug = strtr($slug, $map);

    // 3. Replace any non-alphanumeric character (except hyphen) with a hyphen
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);

    // 4. Collapse consecutive hyphens and trim leading/trailing hyphens
    $slug = preg_replace('/-{2,}/', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = 'project';
    }

    // 5. Ensure uniqueness — append -2, -3, … as needed
    $base      = $slug;
    $candidate = $base;
    $suffix    = 1;

    // Table name is already whitelisted above; safe to interpolate.
    $sql = "SELECT COUNT(*) FROM `$table` WHERE `slug` = :slug AND `id` != :excludeId";
    $stmt = $pdo->prepare($sql);

    do {
        $stmt->execute([':slug' => $candidate, ':excludeId' => $excludeId]);
        $exists = (int) $stmt->fetchColumn() > 0;

        if ($exists) {
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }
    } while ($exists);

    return $candidate;
}

// ──────────────────────────────────────────────────────────────────────────────
// Input / Output
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Trim and HTML-encode a string for safe output in HTML contexts.
 */
function sanitize_input(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Send a redirect header and terminate execution.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

// ──────────────────────────────────────────────────────────────────────────────
// File helpers
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Safely delete a file that lives inside UPLOAD_PATH.
 * Refuses to delete anything outside that directory.
 *
 * @return bool true on success, false if skipped or failed
 */
function delete_file(string $path): bool
{
    // Resolve symlinks / relative segments so the prefix check is reliable
    $realPath   = realpath($path);
    $realUpload = realpath(UPLOAD_PATH);

    if ($realPath === false || $realUpload === false) {
        return false;
    }

    // Enforce that the file lives inside UPLOAD_PATH
    if (strpos($realPath, $realUpload . DIRECTORY_SEPARATOR) !== 0) {
        error_log('[Maison1815] delete_file() refused to delete outside UPLOAD_PATH: ' . $path);
        return false;
    }

    if (!file_exists($realPath)) {
        return false;
    }

    return unlink($realPath);
}

/**
 * Return a human-readable file size string (e.g. "1.4 MB").
 */
function format_filesize(int $bytes): string
{
    if ($bytes >= 1_073_741_824) {
        return round($bytes / 1_073_741_824, 1) . ' GB';
    }

    if ($bytes >= 1_048_576) {
        return round($bytes / 1_048_576, 1) . ' MB';
    }

    if ($bytes >= 1_024) {
        return round($bytes / 1_024, 1) . ' KB';
    }

    return $bytes . ' B';
}

// ──────────────────────────────────────────────────────────────────────────────
// CSRF
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Return (and lazily create) the CSRF token for the current session.
 * Call this once per form render; reuses the same token within a session.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token submitted by the user.
 *
 * @param string $token Value from the submitted form field
 * @return bool true only when the token matches the session value
 */
function csrf_verify(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// ──────────────────────────────────────────────────────────────────────────────
// Flash messages
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Store a flash message under $key for the next page load.
 */
function flash(string $key, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash'][$key] = $message;
}

/**
 * Retrieve and clear a flash message.
 * Returns null if no message is stored under $key.
 */
function get_flash(string $key): ?string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}
