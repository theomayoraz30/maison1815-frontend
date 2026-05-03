<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/projects/index.php#video');
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid CSRF token. Action aborted.');
    redirect('/admin/projects/index.php#video');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Invalid project ID.');
    redirect('/admin/projects/index.php#video');
}

// Load project to retrieve file paths before deletion
$stmt = $pdo->prepare("SELECT `title`, `video_path` FROM `video_projects` WHERE `id` = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    flash('error', 'Video project not found.');
    redirect('/admin/projects/index.php#video');
}

// Delete the video file if one is stored
if (!empty($project['video_path'])) {
    delete_file(BASE_PATH . $project['video_path']);
}

// Delete the project row — ON DELETE CASCADE removes video_project_teams rows
$del = $pdo->prepare("DELETE FROM `video_projects` WHERE `id` = ?");
$del->execute([$id]);

flash('success', 'Video project "' . $project['title'] . '" has been deleted.');
redirect('/admin/projects/index.php#video');
