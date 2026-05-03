<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/projects/index.php#photo');
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid CSRF token. Action aborted.');
    redirect('/admin/projects/index.php#photo');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Invalid project ID.');
    redirect('/admin/projects/index.php#photo');
}

// Load project for file cleanup
$stmt = $pdo->prepare("SELECT `title`, `cover_photo` FROM `photo_projects` WHERE `id` = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    flash('error', 'Photo project not found.');
    redirect('/admin/projects/index.php#photo');
}

// Fetch all gallery image paths for file deletion before row removal
$imgStmt = $pdo->prepare("SELECT `image_path` FROM `photo_project_images` WHERE `project_id` = ?");
$imgStmt->execute([$id]);
$galleryImages = $imgStmt->fetchAll();

// Delete cover photo file
if (!empty($project['cover_photo'])) {
    delete_file(BASE_PATH . $project['cover_photo']);
}

// Delete all gallery image files
foreach ($galleryImages as $img) {
    if (!empty($img['image_path'])) {
        delete_file(BASE_PATH . $img['image_path']);
    }
}

// Delete the project row — CASCADE removes photo_project_images and photo_project_teams
$del = $pdo->prepare("DELETE FROM `photo_projects` WHERE `id` = ?");
$del->execute([$id]);

flash('success', 'Photo project "' . $project['title'] . '" has been deleted.');
redirect('/admin/projects/index.php#photo');
