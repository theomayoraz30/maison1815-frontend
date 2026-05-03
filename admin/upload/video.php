<?php
/**
 * POST /admin/upload/video.php
 * Accepts a video file upload and moves it to UPLOAD_PATH/videos/.
 * Returns JSON. Called via XHR from UploadManager.
 */

declare(strict_types=1);

header('Content-Type: application/json');

// Runtime overrides — must come before any file read
@ini_set('upload_max_filesize', '2G');
@ini_set('post_max_size', '2G');
@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

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

// ── File presence ────────────────────────────────────────────────────────────
error_log("POST SIZE: " . count($_POST) . " FILES SIZE: " . count($_FILES) . " CONTENT: " . print_r($_FILES, true));
if (empty($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $uploadError = $_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload stopped by PHP extension',
    ];
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $errorMessages[$uploadError] ?? 'Upload error code ' . $uploadError,
    ]);
    exit;
}

$file     = $_FILES['video'];
$tmpPath  = $file['tmp_name'];
$fileSize = $file['size'];

// ── Size validation ───────────────────────────────────────────────────────────
if ($fileSize > MAX_VIDEO_SIZE) {
    http_response_code(413);
    echo json_encode([
        'success' => false,
        'error'   => 'File too large. Maximum allowed size is 2 GB.',
    ]);
    exit;
}

// ── Extension validation ─────────────────────────────────────────────────────
$originalName = $file['name'];
$ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_VIDEO_FORMATS, true)) {
    http_response_code(415);
    echo json_encode([
        'success' => false,
        'error'   => 'Unsupported file format. Allowed: ' . implode(', ', ALLOWED_VIDEO_FORMATS),
    ]);
    exit;
}

// ── MIME type validation ──────────────────────────────────────────────────────
$allowedMimes = [
    'video/mp4',
    'video/quicktime',
    'video/x-msvideo',
    'video/x-matroska',
    'video/webm',
    'video/x-ms-wmv',
    'video/x-m4v',
    'video/x-flv',
];

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(415);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid MIME type detected: ' . $mimeType,
    ]);
    exit;
}

// ── Filename construction ─────────────────────────────────────────────────────
$rawSlug    = $_POST['project_slug'] ?? '';
$slug       = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($rawSlug)));
$slug       = substr($slug, 0, 80);
$slug       = $slug !== '' ? $slug : 'video';
$filename   = time() . '_' . $slug . '.' . $ext;

// ── Destination directory ─────────────────────────────────────────────────────
$destDir = UPLOAD_PATH . 'videos/';

if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create upload directory']);
        exit;
    }
}

$destPath = $destDir . $filename;

// ── Move file ─────────────────────────────────────────────────────────────────
if (!move_uploaded_file($tmpPath, $destPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

// ── Success ───────────────────────────────────────────────────────────────────
echo json_encode([
    'success'  => true,
    'path'     => '/uploads/videos/' . $filename,
    'filename' => $filename,
    'size'     => $fileSize,
]);
