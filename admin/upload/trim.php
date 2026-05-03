<?php
/**
 * POST /admin/upload/trim.php
 * Saves clip_start / clip_end timestamps for a video project.
 * Returns JSON.
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

const TRIM_MAX_DURATION = 15.0; // seconds

// ── Method guard ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
$csrfToken = trim($_POST['csrf_token'] ?? '');
if (!csrf_verify($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// ── Type guard (only 'video' supported) ──────────────────────────────────────
$type = trim($_POST['type'] ?? '');
if ($type !== 'video') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported type. Only "video" is supported.']);
    exit;
}

// ── Input parsing ─────────────────────────────────────────────────────────────
$projectId  = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$clipStart  = filter_input(INPUT_POST, 'clip_start', FILTER_VALIDATE_FLOAT);
$clipEnd    = filter_input(INPUT_POST, 'clip_end', FILTER_VALIDATE_FLOAT);

// filter_input returns false on failure, null if absent — normalise to false
$projectId = ($projectId !== false && $projectId !== null) ? (int) $projectId : false;
$clipStart = ($clipStart !== false && $clipStart !== null) ? (float) $clipStart : false;
$clipEnd   = ($clipEnd   !== false && $clipEnd   !== null) ? (float) $clipEnd   : false;

// ── Value validation ──────────────────────────────────────────────────────────
if ($projectId === false || $projectId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'project_id must be a positive integer']);
    exit;
}

if ($clipStart === false || $clipStart < 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'clip_start must be >= 0']);
    exit;
}

if ($clipEnd === false || $clipEnd <= $clipStart) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'clip_end must be greater than clip_start']);
    exit;
}

$duration = $clipEnd - $clipStart;
if ($duration > TRIM_MAX_DURATION) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error'   => 'Clip duration exceeds maximum of ' . TRIM_MAX_DURATION . ' seconds (got ' . round($duration, 2) . 's)',
    ]);
    exit;
}

// ── Verify project exists ─────────────────────────────────────────────────────
try {
    $stmtCheck = $pdo->prepare('SELECT id FROM video_projects WHERE id = :id LIMIT 1');
    $stmtCheck->execute([':id' => $projectId]);

    if ($stmtCheck->fetchColumn() === false) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Project not found']);
        exit;
    }
} catch (PDOException $e) {
    error_log('[Maison1815] trim.php SELECT: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error while verifying project']);
    exit;
}

// ── Persist timestamps ────────────────────────────────────────────────────────
try {
    $stmtUpdate = $pdo->prepare(
        'UPDATE video_projects SET clip_start = :clip_start, clip_end = :clip_end, updated_at = NOW() WHERE id = :id'
    );
    $stmtUpdate->execute([
        ':clip_start' => $clipStart,
        ':clip_end'   => $clipEnd,
        ':id'         => $projectId,
    ]);
} catch (PDOException $e) {
    error_log('[Maison1815] trim.php UPDATE: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error while saving clip timestamps']);
    exit;
}

// ── Success ───────────────────────────────────────────────────────────────────
echo json_encode(['success' => true]);
