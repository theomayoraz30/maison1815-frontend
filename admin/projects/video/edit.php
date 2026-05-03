<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Load project ─────────────────────────────────────────────────────────────────
$projectId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($projectId <= 0) {
    redirect('/admin/projects/index.php#video');
}

$stmt = $pdo->prepare("SELECT * FROM `video_projects` WHERE `id` = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    flash('error', 'Video project not found.');
    redirect('/admin/projects/index.php#video');
}

// Load team members ────────────────────────────────────────────────────────────
$teamStmt = $pdo->prepare("SELECT * FROM `video_project_teams` WHERE `project_id` = ? ORDER BY `id` ASC");
$teamStmt->execute([$projectId]);
$existingTeam = $teamStmt->fetchAll();

$pageTitle  = 'Edit: ' . htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8');
$activePage = 'projects';

$errors = [];
$old    = [
    'client'      => $project['client'],
    'title'       => $project['title'],
    'description' => $project['description'] ?? '',
    'director'    => $project['director'],
    'is_active'   => (bool)$project['is_active'],
    'video_path'  => $project['video_path'] ?? '',
    'clip_start'  => (float)$project['clip_start'],
    'clip_end'    => (float)$project['clip_end'],
    'slug'        => $project['slug'],
    'team'        => $existingTeam,
];

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $client      = trim($_POST['client']      ?? '');
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $director    = trim($_POST['director']    ?? '');
        $isActive    = isset($_POST['is_active']) ? 1 : 0;
        $videoPath   = trim($_POST['video_path']  ?? '');
        $clipStart   = (float)($_POST['clip_start'] ?? 0);
        $clipEnd     = (float)($_POST['clip_end']   ?? 10);

        $old['client']      = $client;
        $old['title']       = $title;
        $old['description'] = $description;
        $old['director']    = $director;
        $old['is_active']   = (bool)$isActive;
        $old['clip_start']  = $clipStart;
        $old['clip_end']    = $clipEnd;

        if ($videoPath !== '') {
            $old['video_path'] = $videoPath;
        }

        // Team members
        $teamFirst = $_POST['team_first'] ?? [];
        $teamLast  = $_POST['team_last']  ?? [];
        $teamRows  = [];
        foreach ($teamFirst as $i => $fn) {
            $fn = trim($fn);
            $ln = trim($teamLast[$i] ?? '');
            if ($fn !== '' || $ln !== '') {
                $teamRows[] = ['first' => $fn, 'last' => $ln];
            }
        }
        $old['team'] = $teamRows;

        if ($client === '') $errors[] = 'Client is required.';
        if ($title === '')  $errors[] = 'Project title is required.';

        if (empty($errors)) {
            // Delete old video file if a new one was uploaded
            if ($videoPath !== '' && !empty($project['video_path']) && $videoPath !== $project['video_path']) {
                delete_file(BASE_PATH . $project['video_path']);
            }

            // Keep existing slug — only recalculate if title changed and we want to update
            // (design decision: slug stays stable on edit)
            $updateStmt = $pdo->prepare(
                "UPDATE `video_projects`
                 SET `client`      = ?,
                     `title`       = ?,
                     `description` = ?,
                     `director`    = ?,
                     `video_path`  = ?,
                     `clip_start`  = ?,
                     `clip_end`    = ?,
                     `is_active`   = ?
                 WHERE `id` = ?"
            );
            $updateStmt->execute([
                $client,
                $title,
                $description,
                $director,
                $videoPath !== '' ? $videoPath : ($project['video_path'] ?? null),
                $clipStart,
                $clipEnd,
                $isActive,
                $projectId,
            ]);

            // Replace team members: delete old, insert new
            $pdo->prepare("DELETE FROM `video_project_teams` WHERE `project_id` = ?")->execute([$projectId]);

            if (!empty($teamRows)) {
                $insertTeam = $pdo->prepare(
                    "INSERT INTO `video_project_teams` (`project_id`, `first_name`, `last_name`) VALUES (?, ?, ?)"
                );
                foreach ($teamRows as $member) {
                    $insertTeam->execute([$projectId, $member['first'], $member['last']]);
                }
            }

            flash('success', 'Video project updated successfully.');
            redirect('/admin/projects/index.php#video');
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';

$inputStyle  = 'background:#111; border:1px solid #2a2a2a; color:#fff; padding:8px 12px; border-radius:3px; width:100%; outline:none; font-family:inherit; font-size:13px;';
$labelStyle  = 'display:block; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; margin-bottom:6px;';
$sectionStyle = 'font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; margin:0 0 12px;';
?>

<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

<!-- Back link -->
<div style="margin-bottom:20px;">
    <a href="/admin/projects/index.php#video"
       style="display:inline-flex; align-items:center; gap:6px; color:#888; text-decoration:none; font-size:12px; letter-spacing:0.05em; text-transform:uppercase; transition:color 0.15s;"
       onmouseover="this.style.color='#fff'"
       onmouseout="this.style.color='#888'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Projects
    </a>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#450a0a; border:1px solid #dc2626; color:#ef4444; border-radius:4px; padding:12px 16px; margin-bottom:20px; font-size:13px;">
    <?php foreach ($errors as $e): ?>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/admin/projects/video/edit.php?id=<?= $projectId ?>" id="project-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id"         value="<?= $projectId ?>">
    <input type="hidden" name="video_path" id="video_path" value="<?= htmlspecialchars($old['video_path'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="clip_start" id="clip_start" value="<?= htmlspecialchars((string)$old['clip_start'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="clip_end"   id="clip_end"   value="<?= htmlspecialchars((string)$old['clip_end'], ENT_QUOTES, 'UTF-8') ?>">

    <div style="display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start;">

        <!-- LEFT -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Project Info -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Project Info</p>
                <div style="display:flex; flex-direction:column; gap:16px;">

                    <div>
                        <label for="client" style="<?= $labelStyle ?>">Client <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="client" id="client" required
                               value="<?= htmlspecialchars($old['client'], ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Client name"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                    </div>

                    <div>
                        <label for="title" style="<?= $labelStyle ?>">Project Title <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="title" id="title" required
                               value="<?= htmlspecialchars($old['title'], ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="e.g. Summer Campaign 2025"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                        <!-- Slug is read-only in edit mode -->
                        <p style="margin:4px 0 0; font-size:11px; color:#888;">
                            Slug: <span style="color:#FF5500; font-family:monospace;"><?= htmlspecialchars($old['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="color:#555; margin-left:4px;">(fixed)</span>
                        </p>
                    </div>

                    <div>
                        <label for="director" style="<?= $labelStyle ?>">Director</label>
                        <input type="text" name="director" id="director"
                               value="<?= htmlspecialchars($old['director'], ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Director name"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                    </div>

                    <div>
                        <label for="description" style="<?= $labelStyle ?>">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  placeholder="Short project description…"
                                  style="<?= $inputStyle ?> resize:vertical; min-height:90px;"
                                  onfocus="this.style.borderColor='#FF5500'"
                                  onblur="this.style.borderColor='#2a2a2a'"><?= htmlspecialchars($old['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Team Members -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Team Members</p>
                <div id="team-list" style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($old['team'] as $member): ?>
                    <div class="team-row" style="display:flex; gap:8px; align-items:center;">
                        <input type="text" name="team_first[]"
                               value="<?= htmlspecialchars($member['first_name'] ?? $member['first'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="First Name"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                        <input type="text" name="team_last[]"
                               value="<?= htmlspecialchars($member['last_name'] ?? $member['last'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Last Name"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                        <button type="button"
                                onclick="this.closest('.team-row').remove()"
                                style="flex-shrink:0; width:30px; height:34px; background:none; border:1px solid #2a2a2a; border-radius:3px; color:#888; cursor:pointer; font-size:16px; line-height:1; transition:color 0.15s, border-color 0.15s;"
                                onmouseover="this.style.color='#ef4444'; this.style.borderColor='#ef4444';"
                                onmouseout="this.style.color='#888'; this.style.borderColor='#2a2a2a';">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addTeamMember()"
                        style="margin-top:10px; padding:7px 14px; background:transparent; border:1px dashed #2a2a2a; color:#888; border-radius:3px; font-size:12px; cursor:pointer; font-family:inherit; letter-spacing:0.04em; transition:border-color 0.15s, color 0.15s;"
                        onmouseover="this.style.borderColor='#FF5500'; this.style.color='#FF5500';"
                        onmouseout="this.style.borderColor='#2a2a2a'; this.style.color='#888';">
                    + Add Member
                </button>
            </div>

            <!-- Video Upload -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Video File</p>

                <?php if (!empty($old['video_path'])): ?>
                <!-- Current video -->
                <div style="background:#111; border:1px solid #2a2a2a; border-radius:3px; padding:12px 14px; margin-bottom:16px;">
                    <p style="<?= $labelStyle ?> margin-bottom:8px;">Current Video</p>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <video src="<?= htmlspecialchars(BASE_URL . $old['video_path'], ENT_QUOTES, 'UTF-8') ?>"
                               style="width:160px; height:90px; object-fit:cover; border-radius:2px; background:#000; border:1px solid #2a2a2a; flex-shrink:0;"
                               controls muted preload="metadata"></video>
                        <div>
                            <p style="color:#ccc; font-size:12px; font-family:monospace; margin:0 0 4px;"><?= htmlspecialchars(basename($old['video_path']), ENT_QUOTES, 'UTF-8') ?></p>
                            <p style="color:#888; font-size:11px; margin:0;">Replace by uploading a new file below.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="margin-bottom:12px;">
                    <label style="<?= $labelStyle ?>"><?= !empty($old['video_path']) ? 'Replace Video' : 'Upload Video' ?></label>
                    <label for="video-file-input"
                           style="display:flex; align-items:center; justify-content:center; gap:8px; padding:24px; border:1px dashed #2a2a2a; border-radius:3px; cursor:pointer; background:#111; transition:border-color 0.15s;"
                           onmouseover="this.style.borderColor='#FF5500'"
                           onmouseout="this.style.borderColor='#2a2a2a'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span style="color:#888; font-size:13px;">Click to select video file</span>
                    </label>
                    <input type="file" id="video-file-input" name="video_file_input"
                           accept=".mp4,.mov,.avi,.mkv,.webm,.wmv,.m4v,.flv"
                           style="display:none;">
                </div>

                <div id="upload-progress-container"></div>

                <!-- Trimmer -->
                <div id="trimmer-container" style="<?= empty($old['video_path']) ? 'display:none;' : '' ?> margin-top:20px;">
                    <p style="<?= $sectionStyle ?> margin-bottom:16px;">Clip Preview Trim</p>
                </div>
            </div>

        </div>

        <!-- RIGHT -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Status -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Status</p>
                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; user-select:none;">
                    <div style="position:relative; width:44px; height:24px; flex-shrink:0;">
                        <input type="checkbox" name="is_active" value="1"
                               id="is_active"
                               <?= $old['is_active'] ? 'checked' : '' ?>
                               onchange="updateToggleUI(this)"
                               style="opacity:0; position:absolute; width:0; height:0;">
                        <span id="toggle-track"
                              style="position:absolute; inset:0; border-radius:12px; background:<?= $old['is_active'] ? '#FF5500' : '#2a2a2a' ?>; transition:background 0.2s; cursor:pointer;">
                            <span id="toggle-knob"
                                  style="position:absolute; top:4px; left:<?= $old['is_active'] ? '23px' : '4px' ?>; width:16px; height:16px; border-radius:50%; background:#fff; transition:left 0.2s;"></span>
                        </span>
                    </div>
                    <span style="color:#ccc; font-size:13px;">Active (visible on site)</span>
                </label>
            </div>

            <!-- Meta -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Created</p>
                <p style="color:#888; font-size:12px; margin:0;"><?= htmlspecialchars($project['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Save / Cancel -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px; display:flex; flex-direction:column; gap:10px;">
                <button type="submit"
                        style="width:100%; padding:11px 18px; background:#FF5500; color:#fff; border:none; border-radius:3px; font-size:13px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; cursor:pointer; font-family:inherit; transition:background 0.15s;"
                        onmouseover="this.style.background='#e64d00'"
                        onmouseout="this.style.background='#FF5500'">
                    Save Changes
                </button>
                <a href="/admin/projects/index.php#video"
                   style="display:block; width:100%; padding:11px 18px; background:transparent; color:#888; border:1px solid #2a2a2a; border-radius:3px; font-size:13px; font-weight:500; letter-spacing:0.06em; text-transform:uppercase; text-align:center; text-decoration:none; box-sizing:border-box; transition:color 0.15s, border-color 0.15s;"
                   onmouseover="this.style.color='#fff'; this.style.borderColor='#3a3a3a';"
                   onmouseout="this.style.color='#888'; this.style.borderColor='#2a2a2a';">
                    Cancel
                </a>
            </div>

        </div>
    </div>
</form>

<script type="module">
import { UploadManager } from '/admin/assets/js/upload.js?v=3';
import { VideoTrimmer }  from '/admin/assets/js/trimmer.js?v=3';

// ── Toggle UI ─────────────────────────────────────────────────────────────────
window.updateToggleUI = function (checkbox) {
    document.getElementById('toggle-track').style.background = checkbox.checked ? '#FF5500' : '#2a2a2a';
    document.getElementById('toggle-knob').style.left        = checkbox.checked ? '23px' : '4px';
};

// ── Team member rows ──────────────────────────────────────────────────────────
const inputStyle = 'background:#111; border:1px solid #2a2a2a; color:#fff; padding:8px 12px; border-radius:3px; width:100%; outline:none; font-family:inherit; font-size:13px;';

window.addTeamMember = function () {
    const list = document.getElementById('team-list');
    const row  = document.createElement('div');
    row.className = 'team-row';
    row.style.cssText = 'display:flex; gap:8px; align-items:center;';
    row.innerHTML = `
        <input type="text" name="team_first[]" placeholder="First Name"
               style="${inputStyle}"
               onfocus="this.style.borderColor='#FF5500'"
               onblur="this.style.borderColor='#2a2a2a'">
        <input type="text" name="team_last[]" placeholder="Last Name"
               style="${inputStyle}"
               onfocus="this.style.borderColor='#FF5500'"
               onblur="this.style.borderColor='#2a2a2a'">
        <button type="button"
                onclick="this.closest('.team-row').remove()"
                style="flex-shrink:0; width:30px; height:34px; background:none; border:1px solid #2a2a2a; border-radius:3px; color:#888; cursor:pointer; font-size:16px; line-height:1; transition:color 0.15s, border-color 0.15s;"
                onmouseover="this.style.color='#ef4444'; this.style.borderColor='#ef4444';"
                onmouseout="this.style.color='#888'; this.style.borderColor='#2a2a2a';">✕</button>
    `;
    list.appendChild(row);
    row.querySelector('input').focus();
};

// ── Video Upload + Trimmer ────────────────────────────────────────────────────
const csrf    = document.querySelector('meta[name="csrf-token"]').content;
const manager = new UploadManager('/admin/upload/video.php', '/admin/upload/image.php', csrf);
let   trimmer = null;

// Pre-load trimmer if video already exists
const existingVideoPath = <?= json_encode(!empty($old['video_path']) ? BASE_URL . '/uploads/' . $old['video_path'] : '') ?>;
const existingStart     = <?= json_encode($old['clip_start']) ?>;
const existingEnd       = <?= json_encode($old['clip_end']) ?>;

if (existingVideoPath) {
    const trimContainer = document.getElementById('trimmer-container');
    trimmer = new VideoTrimmer(null, trimContainer, {
        maxDuration:     10,
        defaultDuration: 10,
        onSave: function (ts) {
            document.getElementById('clip_start').value = ts.start;
            document.getElementById('clip_end').value   = ts.end;
        },
    });
    trimmer.loadVideo(existingVideoPath);
    trimmer.setTimestamps(existingStart, existingEnd);
}

manager.initVideoUpload(
    document.getElementById('video-file-input'),
    document.getElementById('upload-progress-container'),
    function (response) {
        document.getElementById('video_path').value = response.path;

        const trimContainer = document.getElementById('trimmer-container');
        trimContainer.style.display = 'block';

        if (!trimmer) {
            trimmer = new VideoTrimmer(null, trimContainer, {
                maxDuration:     10,
                defaultDuration: 10,
                onSave: function (ts) {
                    document.getElementById('clip_start').value = ts.start;
                    document.getElementById('clip_end').value   = ts.end;
                },
            });
        }
        trimmer.loadVideo(response.url ?? response.path);
    }
);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
