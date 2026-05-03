<?php
/**
 * POST /admin/upload/image.php
 * Accepts an image file upload, optionally resizes via GD, and moves it
 * to UPLOAD_PATH/{upload_dir}/.
 * Returns JSON. Called via XHR from UploadManager.
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Increase memory limit to handle large image resizing (e.g. 34MB images)
ini_set('memory_limit', '512M');

const MAX_IMAGE_SIZE = MAX_VIDEO_SIZE;
const IMAGE_MAX_WIDTH = 3000;

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

// ── Field name (configurable, default 'image') ────────────────────────────────
$fieldName = trim($_POST['field_name'] ?? 'image');
// Sanitise: only allow simple identifiers to prevent $_FILES key injection
if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fieldName)) {
    $fieldName = 'image';
}

// ── File presence ────────────────────────────────────────────────────────────
if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
    $uploadError = $_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE;
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

$file    = $_FILES[$fieldName];
$tmpPath = $file['tmp_name'];

// ── Size validation ───────────────────────────────────────────────────────────
if ($file['size'] > MAX_IMAGE_SIZE) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum allowed size is 10 MB.']);
    exit;
}

// ── Extension validation ─────────────────────────────────────────────────────
$originalName = $file['name'];
$ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_IMAGE_FORMATS, true)) {
    http_response_code(415);
    echo json_encode([
        'success' => false,
        'error'   => 'Unsupported file format. Allowed: ' . implode(', ', ALLOWED_IMAGE_FORMATS),
    ]);
    exit;
}

// ── MIME type validation ──────────────────────────────────────────────────────
$allowedMimes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(415);
    echo json_encode(['success' => false, 'error' => 'Invalid MIME type: ' . $mimeType]);
    exit;
}

// ── Upload directory whitelist ────────────────────────────────────────────────
$allowedDirs = ['photos', 'about', 'team', 'talents', 'thumbnails'];
$uploadDir   = trim($_POST['upload_dir'] ?? 'photos');

if (!in_array($uploadDir, $allowedDirs, true)) {
    $uploadDir = 'photos';
}

// ── Filename construction ─────────────────────────────────────────────────────
$baseName = pathinfo($originalName, PATHINFO_FILENAME);
// Strip everything except safe chars to prevent path traversal
$baseName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $baseName);
$baseName = substr($baseName, 0, 80);
$baseName = $baseName !== '' ? $baseName : 'image';
$filename = time() . '_' . $baseName . '.' . $ext;

// ── Destination directory ─────────────────────────────────────────────────────
$destDir = UPLOAD_PATH . $uploadDir . '/';

if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create upload directory']);
        exit;
    }
}

$destPath = $destDir . $filename;

// ── GD resize + save (or plain copy) ─────────────────────────────────────────
if (!resize_image_if_needed($tmpPath, $destPath, IMAGE_MAX_WIDTH)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save image']);
    exit;
}

// ── Success ───────────────────────────────────────────────────────────────────
echo json_encode([
    'success'  => true,
    'path'     => '/uploads/' . $uploadDir . '/' . $filename,
    'filename' => $filename,
]);

// ── GD helper ─────────────────────────────────────────────────────────────────

/**
 * Copy $sourcePath to $destPath, resizing down to $maxWidth if wider.
 * Supports JPEG, PNG, WebP, GIF. Returns true on success.
 */
function resize_image_if_needed(string $sourcePath, string $destPath, int $maxWidth = 3000): bool
{
    $info = getimagesize($sourcePath);

    if ($info === false) {
        return false;
    }

    $srcWidth  = $info[0];
    $srcHeight = $info[1];
    $imageType = $info[2]; // IMAGETYPE_* constant

    // No resize needed — plain copy is faster
    if ($srcWidth <= $maxWidth) {
        return copy($sourcePath, $destPath);
    }

    // Calculate scaled dimensions preserving aspect ratio
    $scale     = $maxWidth / $srcWidth;
    $newWidth  = $maxWidth;
    $newHeight = (int) round($srcHeight * $scale);

    // Load source image into GD resource
    $srcImage = match ($imageType) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
        IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
        IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
        IMAGETYPE_GIF  => imagecreatefromgif($sourcePath),
        default        => false,
    };

    if ($srcImage === false) {
        return false;
    }

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($dstImage === false) {
        imagedestroy($srcImage);
        return false;
    }

    // Preserve transparency for PNG and WebP
    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
    }

    // Preserve transparency for GIF
    if ($imageType === IMAGETYPE_GIF) {
        $transparentIndex = imagecolortransparent($srcImage);
        if ($transparentIndex >= 0) {
            $transparentColor = imagecolorsforindex($srcImage, $transparentIndex);
            $newTransparent   = imagecolorallocate($dstImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
            if ($newTransparent !== false) {
                imagefill($dstImage, 0, 0, $newTransparent);
                imagecolortransparent($dstImage, $newTransparent);
            }
        }
    }

    $resampled = imagecopyresampled(
        $dstImage, $srcImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $srcWidth, $srcHeight
    );

    if (!$resampled) {
        imagedestroy($srcImage);
        imagedestroy($dstImage);
        return false;
    }

    $saved = match ($imageType) {
        IMAGETYPE_JPEG => imagejpeg($dstImage, $destPath, 88),
        IMAGETYPE_PNG  => imagepng($dstImage, $destPath, 6),
        IMAGETYPE_WEBP => imagewebp($dstImage, $destPath, 88),
        IMAGETYPE_GIF  => imagegif($dstImage, $destPath),
        default        => false,
    };

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return $saved;
}
