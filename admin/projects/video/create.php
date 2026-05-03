<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pageTitle  = 'New Video Project';
$activePage = 'projects';

$errors = [];
$old    = [
    'client'      => '',
    'title'       => '',
    'description' => '',
    'director'    => '',
    'is_active'   => true,
    'video_path'  => '',
    'clip_start'  => 0,
    'clip_end'    => 10,
    'team'        => [],
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

        // Persist values for re-render on error
        $old['client']      = $client;
        $old['title']       = $title;
        $old['description'] = $description;
        $old['director']    = $director;
        $old['is_active']   = (bool)$isActive;
        $old['video_path']  = $videoPath;
        $old['clip_start']  = $clipStart;
        $old['clip_end']    = $clipEnd;

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

        // Validation
        if ($client === '') {
            $errors[] = 'Client is required.';
        }
        if ($title === '') {
            $errors[] = 'Project title is required.';
        }

        if (empty($errors)) {
            $slug = generate_slug($title, $pdo, 'video_projects');

            $stmt = $pdo->prepare(
                "INSERT INTO `video_projects`
                    (`slug`, `client`, `title`, `description`, `director`,
                     `video_path`, `clip_start`, `clip_end`, `is_active`, `sort_order`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
            );
            $stmt->execute([
                $slug,
                $client,
                $title,
                $description,
                $director,
                $videoPath !== '' ? $videoPath : null,
                $clipStart,
                $clipEnd,
                $isActive,
            ]);

            $projectId = (int)$pdo->lastInsertId();

            // Insert team members
            if (!empty($teamRows)) {
                $teamStmt = $pdo->prepare(
                    "INSERT INTO `video_project_teams` (`project_id`, `first_name`, `last_name`) VALUES (?, ?, ?)"
                );
                foreach ($teamRows as $member) {
                    $teamStmt->execute([$projectId, $member['first'], $member['last']]);
                }
            }

            flash('success', 'Video project "' . $title . '" created successfully.');
            redirect('/admin/projects/index.php#video');
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';

// Input style constant for DRY templates
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
        <div style="display:flex; align-items:center; gap:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/admin/projects/video/create.php" id="project-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="video_path"  id="video_path"  value="<?= htmlspecialchars($old['video_path'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="clip_start"  id="clip_start"  value="<?= htmlspecialchars((string)$old['clip_start'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="clip_end"    id="clip_end"    value="<?= htmlspecialchars((string)$old['clip_end'], ENT_QUOTES, 'UTF-8') ?>">

    <div style="display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start;">

        <!-- LEFT COLUMN: main fields -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Client -->
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
                        <p style="margin:4px 0 0; font-size:11px; color:#888;">
                            Slug: <span id="slug-value" style="color:#FF5500; font-family:monospace;">—</span>
                        </p>
                        <!-- Hidden slug display used by UploadManager to read the project slug -->
                        <input type="hidden" id="project-slug-display" value="">
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
                               value="<?= htmlspecialchars($member['first'], ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="First Name"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                        <input type="text" name="team_last[]"
                               value="<?= htmlspecialchars($member['last'], ENT_QUOTES, 'UTF-8') ?>"
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
                <div style="background:#111; border:1px solid #2a2a2a; border-radius:3px; padding:10px 14px; margin-bottom:12px; display:flex; align-items:center; gap:10px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="color:#888; font-size:12px;">Uploaded:</span>
                    <span style="color:#ccc; font-size:12px; font-family:monospace;"><?= htmlspecialchars(basename($old['video_path']), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <div style="margin-bottom:12px;">
                    <label style="<?= $labelStyle ?>">Upload Video</label>
                    <label for="video-file-input"
                           style="display:flex; align-items:center; justify-content:center; gap:8px; padding:28px; border:1px dashed #2a2a2a; border-radius:3px; cursor:pointer; background:#111; transition:border-color 0.15s;"
                           onmouseover="this.style.borderColor='#FF5500'"
                           onmouseout="this.style.borderColor='#2a2a2a'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span style="color:#888; font-size:13px;">Click to select video file</span>
                    </label>
                    <input type="file" id="video-file-input" name="video_file_input"
                           accept=".mp4,.mov,.avi,.mkv,.webm,.wmv,.m4v,.flv"
                           style="display:none;">
                </div>

                <!-- Progress container -->
                <div id="upload-progress-container"></div>

                <!-- Trimmer container (shown after successful upload) -->
                <div id="trimmer-container" style="display:none; margin-top:20px;">
                    <p style="<?= $sectionStyle ?> margin-bottom:16px;">Clip Preview Trim</p>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: settings -->
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

            <!-- Save / Cancel -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px; display:flex; flex-direction:column; gap:10px;">
                <button type="submit"
                        style="width:100%; padding:11px 18px; background:#FF5500; color:#fff; border:none; border-radius:3px; font-size:13px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; cursor:pointer; font-family:inherit; transition:background 0.15s;"
                        onmouseover="this.style.background='#e64d00'"
                        onmouseout="this.style.background='#FF5500'">
                    Save Project
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

// ── Slug preview ──────────────────────────────────────────────────────────────
function slugify(str) {
    const map = {
        'à':'a','á':'a','â':'a','ã':'a','ä':'a','å':'a','æ':'ae','ç':'c',
        'è':'e','é':'e','ê':'e','ë':'e','ì':'i','í':'i','î':'i','ï':'i',
        'ð':'d','ñ':'n','ò':'o','ó':'o','ô':'o','õ':'o','ö':'o','ø':'o',
        'œ':'oe','ù':'u','ú':'u','û':'u','ü':'u','ý':'y','ÿ':'y','ß':'ss',
        'þ':'th','&':'and','‘':'-','’':'-','“':'-','”':'-',
    };
    let s = str.toLowerCase().trim();
    for (const [k, v] of Object.entries(map)) s = s.split(k).join(v);
    s = s.replace(/[^a-z0-9\-]+/g, '-').replace(/-{2,}/g, '-').replace(/^-+|-+$/g, '');
    return s || 'project';
}

const titleInput  = document.getElementById('title');
const slugValue   = document.getElementById('slug-value');
const slugDisplay = document.getElementById('project-slug-display');

titleInput.addEventListener('input', function () {
    const s = slugify(titleInput.value);
    slugValue.textContent   = s;
    slugDisplay.value       = s;
});

// Trigger on load if we have a pre-filled value (error re-render)
if (titleInput.value) {
    const s = slugify(titleInput.value);
    slugValue.textContent = s;
    slugDisplay.value     = s;
}

// ── Toggle UI helper ──────────────────────────────────────────────────────────
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
const csrf      = document.querySelector('meta[name="csrf-token"]').content;
const manager   = new UploadManager('/admin/upload/video.php', '/admin/upload/image.php', csrf);
let   trimmer   = null;

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
