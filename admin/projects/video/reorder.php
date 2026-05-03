<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$rawIds = $_POST['ids'] ?? '';

if (!is_string($rawIds) || $rawIds === '') {
    echo json_encode(['success' => false, 'error' => 'No ids provided']);
    exit;
}

$ids = json_decode($rawIds, true);

if (!is_array($ids)) {
    echo json_encode(['success' => false, 'error' => 'Invalid ids format']);
    exit;
}

$stmt = $pdo->prepare("UPDATE `video_projects` SET `sort_order` = ? WHERE `id` = ?");

foreach ($ids as $position => $id) {
    $id       = (int)$id;
    $position = (int)$position;

    if ($id <= 0) {
        continue;
    }

    $stmt->execute([$position, $id]);
}

echo json_encode(['success' => true]);
