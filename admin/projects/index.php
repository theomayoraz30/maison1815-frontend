<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pageTitle  = 'Projects';
$activePage = 'projects';

// ── AJAX: toggle active/inactive ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    header('Content-Type: application/json');

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $type = $_POST['type'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    $allowedTables = ['video' => 'video_projects', 'photo' => 'photo_projects'];
    $table = $allowedTables[$type] ?? null;

    if ($table === null || $id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE `{$table}` SET `is_active` = NOT `is_active` WHERE `id` = ?");
    $stmt->execute([$id]);

    $row = $pdo->prepare("SELECT `is_active` FROM `{$table}` WHERE `id` = ?");
    $row->execute([$id]);
    $newVal = (int)$row->fetchColumn();

    echo json_encode(['success' => true, 'is_active' => $newVal]);
    exit;
}

// ── Fetch projects ────────────────────────────────────────────────────────────
$videoProjects = $pdo->query(
    "SELECT * FROM `video_projects` ORDER BY `sort_order` ASC, `created_at` DESC"
)->fetchAll();

$photoProjects = $pdo->query(
    "SELECT * FROM `photo_projects` ORDER BY `sort_order` ASC, `created_at` DESC"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

<!-- ── Page header ─────────────────────────────────────────────────────────── -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
    <div></div><!-- title already rendered by header.php -->
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="/admin/projects/video/create.php" id="btn-new-video"
           style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#FF5500; color:#fff; text-decoration:none; border-radius:3px; font-size:12px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; transition:background 0.15s;"
           onmouseover="this.style.background='#e64d00'"
           onmouseout="this.style.background='#FF5500'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Video Project
        </a>
        <a href="/admin/projects/photo/create.php" id="btn-new-photo"
           style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#FF5500; color:#fff; text-decoration:none; border-radius:3px; font-size:12px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; transition:background 0.15s;"
           onmouseover="this.style.background='#e64d00'"
           onmouseout="this.style.background='#FF5500'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Photo Project
        </a>
    </div>
</div>

<!-- ── Tab toggle ──────────────────────────────────────────────────────────── -->
<div style="display:flex; gap:0; margin-bottom:28px; border:1px solid #2a2a2a; border-radius:4px; width:fit-content;">
    <button id="tab-video" onclick="switchTab('video')"
            style="padding:8px 24px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; cursor:pointer; border:none; border-radius:3px 0 0 3px; font-weight:600; font-family:inherit; background:#FF5500; color:#fff; transition:background 0.15s, color 0.15s;">
        Video <span id="count-video">(<?= count($videoProjects) ?>)</span>
    </button>
    <button id="tab-photo" onclick="switchTab('photo')"
            style="padding:8px 24px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; cursor:pointer; border:none; border-radius:0 3px 3px 0; font-weight:600; font-family:inherit; background:#1a1a1a; color:#888; transition:background 0.15s, color 0.15s;">
        Photo <span id="count-photo">(<?= count($photoProjects) ?>)</span>
    </button>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     VIDEO PANEL
══════════════════════════════════════════════════════════════════════════ -->
<div id="panel-video">

    <?php if (empty($videoProjects)): ?>
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:48px; text-align:center;">
            <p style="color:#888; font-size:14px; margin:0 0 16px;">No video projects yet.</p>
            <a href="/admin/projects/video/create.php"
               style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#FF5500; color:#fff; text-decoration:none; border-radius:3px; font-size:12px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase;">
                + Create First Video Project
            </a>
        </div>
    <?php else: ?>
        <!-- Table -->
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; overflow:hidden; margin-bottom:24px;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #2a2a2a;">
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:100px;">Preview</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500;">Title</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500;">Client</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500;">Director</th>
                        <th style="padding:12px 16px; text-align:center; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:90px;">Status</th>
                        <th style="padding:12px 16px; text-align:center; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:70px;">Order</th>
                        <th style="padding:12px 16px; text-align:right; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videoProjects as $p): ?>
                    <tr style="border-bottom:1px solid #2a2a2a; transition:background 0.1s;"
                        onmouseover="this.style.background='#222'"
                        onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 16px;">
                            <?php if (!empty($p['video_path'])): ?>
                                <video src="<?= htmlspecialchars(BASE_URL . $p['video_path'], ENT_QUOTES, 'UTF-8') ?>"
                                       style="width:80px; height:50px; object-fit:cover; border-radius:2px; display:block; background:#111;"
                                       preload="metadata" muted
                                       onloadedmetadata="this.currentTime=1"></video>
                            <?php else: ?>
                                <div style="width:80px; height:50px; background:#111; border:1px solid #2a2a2a; border-radius:2px; display:flex; align-items:center; justify-content:center;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="color:#fff; font-size:13px; font-weight:500;"><?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="display:block; color:#888; font-size:11px; margin-top:2px;"><?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td style="padding:12px 16px; color:#ccc; font-size:13px;"><?= htmlspecialchars($p['client'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:12px 16px; color:#ccc; font-size:13px;"><?= htmlspecialchars($p['director'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:12px 16px; text-align:center;">
                            <!-- Toggle switch -->
                            <label style="position:relative; display:inline-block; width:40px; height:22px; cursor:pointer;">
                                <input type="checkbox"
                                       <?= $p['is_active'] ? 'checked' : '' ?>
                                       onchange="toggleStatus(<?= (int)$p['id'] ?>, 'video', this)"
                                       style="opacity:0; width:0; height:0; position:absolute;">
                                <span class="toggle-track" data-id="<?= (int)$p['id'] ?>" data-type="video"
                                      style="position:absolute; inset:0; border-radius:11px; background:<?= $p['is_active'] ? '#FF5500' : '#2a2a2a' ?>; transition:background 0.2s; cursor:pointer;">
                                    <span style="position:absolute; top:3px; left:<?= $p['is_active'] ? '21px' : '3px' ?>; width:16px; height:16px; border-radius:50%; background:#fff; transition:left 0.2s;"></span>
                                </span>
                            </label>
                        </td>
                        <td style="padding:12px 16px; text-align:center; color:#888; font-size:13px;"><?= (int)$p['sort_order'] ?></td>
                        <td style="padding:12px 16px; text-align:right;">
                            <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                                <a href="/admin/projects/video/edit.php?id=<?= (int)$p['id'] ?>"
                                   title="Edit"
                                   style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; background:#111; border:1px solid #2a2a2a; border-radius:3px; color:#888; text-decoration:none; transition:border-color 0.15s, color 0.15s;"
                                   onmouseover="this.style.borderColor='#FF5500'; this.style.color='#FF5500';"
                                   onmouseout="this.style.borderColor='#2a2a2a'; this.style.color='#888';">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <button type="button"
                                        onclick="openDeleteModal(<?= (int)$p['id'] ?>, 'video')"
                                        title="Delete"
                                        style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; background:#111; border:1px solid #2a2a2a; border-radius:3px; color:#888; cursor:pointer; transition:border-color 0.15s, color 0.15s;"
                                        onmouseover="this.style.borderColor='#ef4444'; this.style.color='#ef4444';"
                                        onmouseout="this.style.borderColor='#2a2a2a'; this.style.color='#888';">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Drag & Drop Reorder -->
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
            <p style="font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; margin:0 0 12px; font-weight:500;">Drag to Reorder</p>
            <div id="video-reorder-list" style="display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($videoProjects as $p): ?>
                <div data-id="<?= (int)$p['id'] ?>" draggable="true"
                     style="display:flex; align-items:center; gap:10px; padding:10px 14px; background:#111; border:1px solid #2a2a2a; border-radius:3px; cursor:grab; user-select:none; transition:border-color 0.15s;"
                     onmouseover="this.style.borderColor='#3a3a3a'"
                     onmouseout="this.style.borderColor='#2a2a2a'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    <span style="color:#ccc; font-size:13px;"><?= htmlspecialchars($p['title'] ?: '(Untitled)', ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="color:#888; font-size:11px; margin-left:auto;"><?= htmlspecialchars($p['client'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     PHOTO PANEL
══════════════════════════════════════════════════════════════════════════ -->
<div id="panel-photo" style="display:none;">

    <?php if (empty($photoProjects)): ?>
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:48px; text-align:center;">
            <p style="color:#888; font-size:14px; margin:0 0 16px;">No photo projects yet.</p>
            <a href="/admin/projects/photo/create.php"
               style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#FF5500; color:#fff; text-decoration:none; border-radius:3px; font-size:12px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase;">
                + Create First Photo Project
            </a>
        </div>
    <?php else: ?>
        <!-- Table -->
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; overflow:hidden; margin-bottom:24px;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #2a2a2a;">
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:100px;">Thumbnail</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500;">Title</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500;">Client</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500;">Director</th>
                        <th style="padding:12px 16px; text-align:center; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:90px;">Status</th>
                        <th style="padding:12px 16px; text-align:center; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:70px;">Order</th>
                        <th style="padding:12px 16px; text-align:right; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($photoProjects as $p): ?>
                    <tr style="border-bottom:1px solid #2a2a2a; transition:background 0.1s;"
                        onmouseover="this.style.background='#222'"
                        onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 16px;">
                            <?php if (!empty($p['cover_photo'])): ?>
                                <img src="<?= htmlspecialchars(BASE_URL  . $p['cover_photo'], ENT_QUOTES, 'UTF-8') ?>"
                                     style="width:80px; height:50px; object-fit:cover; border-radius:2px; display:block; background:#111;"
                                     alt="<?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div style="width:80px; height:50px; background:#111; border:1px solid #2a2a2a; border-radius:2px; display:flex; align-items:center; justify-content:center;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="color:#fff; font-size:13px; font-weight:500;"><?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="display:block; color:#888; font-size:11px; margin-top:2px;"><?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td style="padding:12px 16px; color:#ccc; font-size:13px;"><?= htmlspecialchars($p['client'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:12px 16px; color:#ccc; font-size:13px;"><?= htmlspecialchars($p['director'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:12px 16px; text-align:center;">
                            <label style="position:relative; display:inline-block; width:40px; height:22px; cursor:pointer;">
                                <input type="checkbox"
                                       <?= $p['is_active'] ? 'checked' : '' ?>
                                       onchange="toggleStatus(<?= (int)$p['id'] ?>, 'photo', this)"
                                       style="opacity:0; width:0; height:0; position:absolute;">
                                <span class="toggle-track" data-id="<?= (int)$p['id'] ?>" data-type="photo"
                                      style="position:absolute; inset:0; border-radius:11px; background:<?= $p['is_active'] ? '#FF5500' : '#2a2a2a' ?>; transition:background 0.2s; cursor:pointer;">
                                    <span style="position:absolute; top:3px; left:<?= $p['is_active'] ? '21px' : '3px' ?>; width:16px; height:16px; border-radius:50%; background:#fff; transition:left 0.2s;"></span>
                                </span>
                            </label>
                        </td>
                        <td style="padding:12px 16px; text-align:center; color:#888; font-size:13px;"><?= (int)$p['sort_order'] ?></td>
                        <td style="padding:12px 16px; text-align:right;">
                            <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                                <a href="/admin/projects/photo/edit.php?id=<?= (int)$p['id'] ?>"
                                   title="Edit"
                                   style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; background:#111; border:1px solid #2a2a2a; border-radius:3px; color:#888; text-decoration:none; transition:border-color 0.15s, color 0.15s;"
                                   onmouseover="this.style.borderColor='#FF5500'; this.style.color='#FF5500';"
                                   onmouseout="this.style.borderColor='#2a2a2a'; this.style.color='#888';">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <button type="button"
                                        onclick="openDeleteModal(<?= (int)$p['id'] ?>, 'photo')"
                                        title="Delete"
                                        style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; background:#111; border:1px solid #2a2a2a; border-radius:3px; color:#888; cursor:pointer; transition:border-color 0.15s, color 0.15s;"
                                        onmouseover="this.style.borderColor='#ef4444'; this.style.color='#ef4444';"
                                        onmouseout="this.style.borderColor='#2a2a2a'; this.style.color='#888';">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Drag & Drop Reorder -->
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
            <p style="font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; margin:0 0 12px; font-weight:500;">Drag to Reorder</p>
            <div id="photo-reorder-list" style="display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($photoProjects as $p): ?>
                <div data-id="<?= (int)$p['id'] ?>" draggable="true"
                     style="display:flex; align-items:center; gap:10px; padding:10px 14px; background:#111; border:1px solid #2a2a2a; border-radius:3px; cursor:grab; user-select:none; transition:border-color 0.15s;"
                     onmouseover="this.style.borderColor='#3a3a3a'"
                     onmouseout="this.style.borderColor='#2a2a2a'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    <span style="color:#ccc; font-size:13px;"><?= htmlspecialchars($p['title'] ?: '(Untitled)', ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="color:#888; font-size:11px; margin-left:auto;"><?= htmlspecialchars($p['client'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ── Delete confirmation modal ───────────────────────────────────────────── -->
<div id="delete-modal"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:100; align-items:center; justify-content:center;">
    <div style="background:#1a1a1a; border:1px solid #2a2a2a; padding:32px; border-radius:4px; max-width:400px; width:100%; margin:0 16px;">
        <h3 style="color:#fff; margin:0 0 8px; font-size:16px; font-weight:600; letter-spacing:0.02em;">Delete Project</h3>
        <p style="color:#888; margin:0 0 24px; font-size:14px; line-height:1.5;">This action cannot be undone. The project and all associated files will be permanently deleted.</p>
        <form method="POST" id="delete-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="delete-id">
            <div style="display:flex; gap:12px;">
                <button type="button"
                        onclick="closeDeleteModal()"
                        style="flex:1; padding:10px; background:#111; border:1px solid #2a2a2a; color:#888; border-radius:3px; cursor:pointer; font-family:inherit; font-size:13px; font-weight:500; transition:border-color 0.15s, color 0.15s;"
                        onmouseover="this.style.borderColor='#3a3a3a'; this.style.color='#fff';"
                        onmouseout="this.style.borderColor='#2a2a2a'; this.style.color='#888';">
                    Cancel
                </button>
                <button type="submit"
                        style="flex:1; padding:10px; background:#ef4444; border:none; color:#fff; border-radius:3px; cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; transition:background 0.15s;"
                        onmouseover="this.style.background='#dc2626'"
                        onmouseout="this.style.background='#ef4444'">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Tab toggle ────────────────────────────────────────────────────────────────
function switchTab(type) {
    document.getElementById('panel-video').style.display = type === 'video' ? 'block' : 'none';
    document.getElementById('panel-photo').style.display = type === 'photo' ? 'block' : 'none';
    document.getElementById('tab-video').style.background = type === 'video' ? '#FF5500' : '#1a1a1a';
    document.getElementById('tab-video').style.color      = type === 'video' ? '#fff' : '#888';
    document.getElementById('tab-photo').style.background = type === 'photo' ? '#FF5500' : '#1a1a1a';
    document.getElementById('tab-photo').style.color      = type === 'photo' ? '#fff' : '#888';
    
    // Toggle action buttons
    const btnVideo = document.getElementById('btn-new-video');
    const btnPhoto = document.getElementById('btn-new-photo');
    if (btnVideo) btnVideo.style.display = type === 'video' ? 'inline-flex' : 'none';
    if (btnPhoto) btnPhoto.style.display = type === 'photo' ? 'inline-flex' : 'none';

    history.replaceState(null, '', '#' + type);
}

// Restore tab from URL hash on load
(function () {
    const hash = location.hash.replace('#', '');
    switchTab(hash === 'photo' ? 'photo' : 'video');
})();

// ── Delete modal ──────────────────────────────────────────────────────────────
function openDeleteModal(id, type) {
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-form').action = '/admin/projects/' + type + '/delete.php';
    const modal = document.getElementById('delete-modal');
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
}

// Close on backdrop click
document.getElementById('delete-modal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});

// ── AJAX status toggle ────────────────────────────────────────────────────────
async function toggleStatus(id, type, checkbox) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fd   = new FormData();
    fd.append('action',     'toggle');
    fd.append('id',         id);
    fd.append('type',       type);
    fd.append('csrf_token', csrf);

    // Update toggle track colour optimistically
    const track = checkbox.closest('label').querySelector('.toggle-track');
    const knob  = track ? track.querySelector('span') : null;

    try {
        const r    = await fetch('/admin/projects/index.php', { method: 'POST', body: fd });
        const data = await r.json();

        if (data.success) {
            const active = data.is_active === 1;
            if (track) {
                track.style.background = active ? '#FF5500' : '#2a2a2a';
            }
            if (knob) {
                knob.style.left = active ? '21px' : '3px';
            }
        } else {
            // Revert checkbox
            checkbox.checked = !checkbox.checked;
        }
    } catch (e) {
        checkbox.checked = !checkbox.checked;
    }
}

// ── Drag & drop reorder ───────────────────────────────────────────────────────
function initReorder(listId, saveUrl, saveBtnId) {
    const list = document.getElementById(listId);
    if (!list) return;

    let dragSrc = null;

    list.querySelectorAll('[draggable]').forEach(function (item) {
        item.addEventListener('dragstart', function (e) {
            dragSrc = item;
            item.style.opacity = '0.4';
            item.style.cursor  = 'grabbing';
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function () {
            item.style.opacity = '1';
            item.style.cursor  = 'grab';
            list.querySelectorAll('[draggable]').forEach(function (el) {
                el.style.borderColor = '#2a2a2a';
            });
        });

        item.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            item.style.borderColor = '#FF5500';
        });

        item.addEventListener('dragleave', function () {
            item.style.borderColor = '#2a2a2a';
        });

        item.addEventListener('drop', function (e) {
            e.preventDefault();
            item.style.borderColor = '#2a2a2a';
            if (dragSrc && dragSrc !== item) {
                const allItems = [...list.querySelectorAll('[draggable]')];
                const fromIdx  = allItems.indexOf(dragSrc);
                const toIdx    = allItems.indexOf(item);
                if (fromIdx < toIdx) {
                    item.after(dragSrc);
                } else {
                    item.before(dragSrc);
                }
                const ids  = [...list.querySelectorAll('[data-id]')].map(function (el) { return parseInt(el.dataset.id, 10); });
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const fd   = new FormData();
                fd.append('ids',        JSON.stringify(ids));
                fd.append('csrf_token', csrf);
                fetch(saveUrl, { method: 'POST', body: fd }).catch(()=>console.error('Failed to save order'));
            }
        });
    });
}

initReorder('video-reorder-list', '/admin/projects/video/reorder.php');
initReorder('photo-reorder-list', '/admin/projects/photo/reorder.php');
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
