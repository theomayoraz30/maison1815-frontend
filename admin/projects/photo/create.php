<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pageTitle  = 'New Photo Project';
$activePage = 'projects';

$errors = [];
$old    = [
    'client'        => '',
    'title'         => '',
    'description'   => '',
    'director'      => '',
    'is_active'     => true,
    'cover_photo'   => '',
    'gallery_photos'=> [],
    'team'          => [],
];

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $client         = trim($_POST['client']       ?? '');
        $title          = trim($_POST['title']        ?? '');
        $description    = trim($_POST['description']  ?? '');
        $director       = trim($_POST['director']     ?? '');
        $isActive       = isset($_POST['is_active']) ? 1 : 0;
        $coverPhoto     = trim($_POST['cover_photo']  ?? '');

        // Gallery images — array of relative paths
        $galleryPhotos  = array_filter(array_map('trim', (array)($_POST['gallery_photos'] ?? [])));

        $old['client']         = $client;
        $old['title']          = $title;
        $old['description']    = $description;
        $old['director']       = $director;
        $old['is_active']      = (bool)$isActive;
        $old['cover_photo']    = $coverPhoto;
        $old['gallery_photos'] = array_values($galleryPhotos);

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
            $slug = generate_slug($title, $pdo, 'photo_projects');

            $stmt = $pdo->prepare(
                "INSERT INTO `photo_projects`
                    (`slug`, `client`, `title`, `description`, `director`,
                     `cover_photo`, `is_active`, `sort_order`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
            );
            $stmt->execute([
                $slug,
                $client,
                $title,
                $description,
                $director,
                $coverPhoto !== '' ? $coverPhoto : null,
                $isActive,
            ]);

            $projectId = (int)$pdo->lastInsertId();

            // Insert team members
            if (!empty($teamRows)) {
                $teamInsert = $pdo->prepare(
                    "INSERT INTO `photo_project_teams` (`project_id`, `first_name`, `last_name`) VALUES (?, ?, ?)"
                );
                foreach ($teamRows as $member) {
                    $teamInsert->execute([$projectId, $member['first'], $member['last']]);
                }
            }

            // Insert gallery images
            if (!empty($galleryPhotos)) {
                $imgInsert = $pdo->prepare(
                    "INSERT INTO `photo_project_images` (`project_id`, `image_path`, `sort_order`) VALUES (?, ?, ?)"
                );
                foreach (array_values($galleryPhotos) as $sortOrder => $path) {
                    $imgInsert->execute([$projectId, $path, $sortOrder]);
                }
            }

            flash('success', 'Photo project "' . $title . '" created successfully.');
            redirect('/admin/projects/index.php#photo');
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';

$inputStyle   = 'background:#111; border:1px solid #2a2a2a; color:#fff; padding:8px 12px; border-radius:3px; width:100%; outline:none; font-family:inherit; font-size:13px;';
$labelStyle   = 'display:block; font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; margin-bottom:6px;';
$sectionStyle = 'font-size:11px; color:#888; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; margin:0 0 12px;';
?>

<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

<!-- Back link -->
<div style="margin-bottom:20px;">
    <a href="/admin/projects/index.php#photo"
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

<form method="POST" action="/admin/projects/photo/create.php" id="project-form">
    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="cover_photo"  id="cover_photo_input" value="<?= htmlspecialchars($old['cover_photo'], ENT_QUOTES, 'UTF-8') ?>">
    <!-- Gallery hidden inputs will be injected by JS -->
    <div id="gallery-hidden-inputs">
        <?php foreach ($old['gallery_photos'] as $path): ?>
            <input type="hidden" name="gallery_photos[]" value="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    </div>

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
                               placeholder="e.g. Editorial Spring 2025"
                               style="<?= $inputStyle ?>"
                               onfocus="this.style.borderColor='#FF5500'"
                               onblur="this.style.borderColor='#2a2a2a'">
                        <p style="margin:4px 0 0; font-size:11px; color:#888;">
                            Slug: <span id="slug-value" style="color:#FF5500; font-family:monospace;">—</span>
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

            <!-- Cover Photo -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Cover Photo</p>

                <div id="cover-preview-wrap" style="<?= empty($old['cover_photo']) ? 'display:none;' : '' ?> margin-bottom:12px;">
                    <img id="cover-preview"
                         src="<?= !empty($old['cover_photo']) ? htmlspecialchars(BASE_URL . $old['cover_photo'], ENT_QUOTES, 'UTF-8') : '' ?>"
                         alt="Cover preview"
                         style="width:100%; max-height:240px; object-fit:cover; border-radius:3px; display:block; border:1px solid #2a2a2a;">
                </div>

                <label for="cover-file-input"
                       style="display:flex; align-items:center; justify-content:center; gap:8px; padding:24px; border:1px dashed #2a2a2a; border-radius:3px; cursor:pointer; background:#111; transition:border-color 0.15s;"
                       onmouseover="this.style.borderColor='#FF5500'"
                       onmouseout="this.style.borderColor='#2a2a2a'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span style="color:#888; font-size:13px;">Click to select cover photo</span>
                </label>
                <input type="file" id="cover-file-input" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;">
            </div>

            <!-- Gallery Photos -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px;">
                <p style="<?= $sectionStyle ?>">Gallery Photos</p>

                <!-- Thumbnail grid (managed by JS) -->
                <div id="gallery-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:8px; margin-bottom:12px;"></div>

                <label for="gallery-file-input"
                       style="display:flex; align-items:center; justify-content:center; gap:8px; padding:20px; border:1px dashed #2a2a2a; border-radius:3px; cursor:pointer; background:#111; transition:border-color 0.15s;"
                       onmouseover="this.style.borderColor='#FF5500'"
                       onmouseout="this.style.borderColor='#2a2a2a'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span style="color:#888; font-size:13px;">Add gallery photos</span>
                </label>
                <input type="file" id="gallery-file-input" accept=".jpg,.jpeg,.png,.webp,.gif" multiple style="display:none;">

                <p style="color:#555; font-size:11px; margin:8px 0 0;">Drag thumbnails to reorder. Click ✕ to remove.</p>
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

            <!-- Save / Cancel -->
            <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:20px; display:flex; flex-direction:column; gap:10px;">
                <button type="submit"
                        style="width:100%; padding:11px 18px; background:#FF5500; color:#fff; border:none; border-radius:3px; font-size:13px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; cursor:pointer; font-family:inherit; transition:background 0.15s;"
                        onmouseover="this.style.background='#e64d00'"
                        onmouseout="this.style.background='#FF5500'">
                    Save Project
                </button>
                <a href="/admin/projects/index.php#photo"
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
import { UploadManager } from '/admin/assets/js/upload.js?v=2';

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

const titleInput = document.getElementById('title');
const slugValue  = document.getElementById('slug-value');
titleInput.addEventListener('input', () => { slugValue.textContent = slugify(titleInput.value); });
if (titleInput.value) slugValue.textContent = slugify(titleInput.value);

// ── Toggle UI ─────────────────────────────────────────────────────────────────
window.updateToggleUI = function (checkbox) {
    document.getElementById('toggle-track').style.background = checkbox.checked ? '#FF5500' : '#2a2a2a';
    document.getElementById('toggle-knob').style.left        = checkbox.checked ? '23px' : '4px';
};

// ── Team member rows ──────────────────────────────────────────────────────────
const fieldStyle = 'background:#111; border:1px solid #2a2a2a; color:#fff; padding:8px 12px; border-radius:3px; width:100%; outline:none; font-family:inherit; font-size:13px;';

window.addTeamMember = function () {
    const list = document.getElementById('team-list');
    const row  = document.createElement('div');
    row.className = 'team-row';
    row.style.cssText = 'display:flex; gap:8px; align-items:center;';
    row.innerHTML = `
        <input type="text" name="team_first[]" placeholder="First Name"
               style="${fieldStyle}" onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        <input type="text" name="team_last[]" placeholder="Last Name"
               style="${fieldStyle}" onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        <button type="button" onclick="this.closest('.team-row').remove()"
                style="flex-shrink:0; width:30px; height:34px; background:none; border:1px solid #2a2a2a; border-radius:3px; color:#888; cursor:pointer; font-size:16px; line-height:1; transition:color 0.15s, border-color 0.15s;"
                onmouseover="this.style.color='#ef4444'; this.style.borderColor='#ef4444';"
                onmouseout="this.style.color='#888'; this.style.borderColor='#2a2a2a';">✕</button>
    `;
    list.appendChild(row);
    row.querySelector('input').focus();
};

// ── Upload Manager ────────────────────────────────────────────────────────────
const csrf    = document.querySelector('meta[name="csrf-token"]').content;
const manager = new UploadManager('/admin/upload/video.php', '/admin/upload/image.php', csrf);

// Cover photo
const coverPreview     = document.getElementById('cover-preview');
const coverPreviewWrap = document.getElementById('cover-preview-wrap');
const coverInput       = document.getElementById('cover_photo_input');

manager.initImageUpload(
    document.getElementById('cover-file-input'),
    coverPreview,
    function (response) {
        coverInput.value = response.path;
        coverPreviewWrap.style.display = 'block';
    },
    'photos'
);

// ── Gallery images ────────────────────────────────────────────────────────────
const galleryGrid         = document.getElementById('gallery-grid');
const galleryHiddenInputs = document.getElementById('gallery-hidden-inputs');
let   galleryDragSrc      = null;

function addGalleryThumbnail(path, previewSrc) {
    // Hidden input for form submission
    const hidden = document.createElement('input');
    hidden.type  = 'hidden';
    hidden.name  = 'gallery_photos[]';
    hidden.value = path;
    galleryHiddenInputs.appendChild(hidden);

    // Visible thumbnail with remove button
    const wrap = document.createElement('div');
    wrap.style.cssText  = 'position:relative; aspect-ratio:1; cursor:grab;';
    wrap.draggable      = true;
    wrap.dataset.path   = path;

    const img        = document.createElement('img');
    img.src          = previewSrc;
    img.style.cssText = 'width:100%; height:100%; object-fit:cover; border-radius:2px; display:block; border:1px solid #2a2a2a;';

    const removeBtn        = document.createElement('button');
    removeBtn.type         = 'button';
    removeBtn.textContent  = '✕';
    removeBtn.style.cssText = 'position:absolute; top:3px; right:3px; width:20px; height:20px; background:rgba(0,0,0,0.75); border:none; border-radius:50%; color:#fff; font-size:11px; cursor:pointer; display:flex; align-items:center; justify-content:center; line-height:1;';
    removeBtn.addEventListener('click', function () {
        // Remove the matching hidden input
        const toRemove = [...galleryHiddenInputs.querySelectorAll('input[type=hidden]')]
            .find(function (el) { return el.value === path; });
        if (toRemove) toRemove.remove();
        wrap.remove();
    });

    wrap.appendChild(img);
    wrap.appendChild(removeBtn);

    // Drag-to-reorder
    wrap.addEventListener('dragstart', function (e) {
        galleryDragSrc = wrap;
        wrap.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
    });
    wrap.addEventListener('dragend', function () {
        wrap.style.opacity = '1';
        syncGalleryHiddenInputs();
    });
    wrap.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });
    wrap.addEventListener('drop', function (e) {
        e.preventDefault();
        if (galleryDragSrc && galleryDragSrc !== wrap) {
            const items   = [...galleryGrid.children];
            const fromIdx = items.indexOf(galleryDragSrc);
            const toIdx   = items.indexOf(wrap);
            if (fromIdx < toIdx) wrap.after(galleryDragSrc);
            else                  wrap.before(galleryDragSrc);
        }
    });

    galleryGrid.appendChild(wrap);
}

// Re-sync hidden inputs to match current DOM order
function syncGalleryHiddenInputs() {
    galleryHiddenInputs.innerHTML = '';
    galleryGrid.querySelectorAll('[data-path]').forEach(function (el) {
        const inp   = document.createElement('input');
        inp.type    = 'hidden';
        inp.name    = 'gallery_photos[]';
        inp.value   = el.dataset.path;
        galleryHiddenInputs.appendChild(inp);
    });
}

// Multiple image file input — upload each sequentially
const galleryFileInput = document.getElementById('gallery-file-input');
galleryFileInput.addEventListener('change', async function () {
    const files = [...galleryFileInput.files];
    galleryFileInput.value = ''; // allow re-selecting same file

    for (const file of files) {
        await uploadGalleryFile(file);
    }
});

function uploadGalleryFile(file) {
    return new Promise(function (resolve) {
        // Preview locally first
        const reader   = new FileReader();
        reader.onload  = function (e) {
            const previewSrc = e.target.result;

            // Skeleton preview
            const skeletonWrap = document.createElement('div');
            skeletonWrap.style.cssText = 'position:relative; aspect-ratio:1; opacity:0.6; filter:grayscale(100%); transition:opacity 0.2s;';
            const skelImg = document.createElement('img');
            skelImg.src = previewSrc;
            skelImg.style.cssText = 'width:100%; height:100%; object-fit:cover; border-radius:2px; display:block; border:1px dashed #FF5500;';
            
            const textWrap = document.createElement('div');
            textWrap.style.cssText = 'position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.5); color:#fff; font-size:10px; font-weight:bold; letter-spacing:0.05em; text-transform:uppercase; border-radius:2px;';
            textWrap.textContent = 'Loading...';
            
            skeletonWrap.appendChild(skelImg);
            skeletonWrap.appendChild(textWrap);
            galleryGrid.appendChild(skeletonWrap);

            const formData = new FormData();
            formData.append('image',      file);
            formData.append('csrf_token', csrf);
            formData.append('upload_dir', 'photos');

            const xhr = new XMLHttpRequest();
            xhr.addEventListener('load', function () {
                let response;
                try { response = JSON.parse(xhr.responseText); } catch { skeletonWrap.remove(); resolve(); return; }
                if (xhr.status === 200 && response.success) {
                    addGalleryThumbnail(response.path, previewSrc);
                }
                skeletonWrap.remove();
                resolve();
            });
            xhr.addEventListener('error', function () { skeletonWrap.remove(); resolve(); });
            xhr.open('POST', '/admin/upload/image.php');
            xhr.send(formData);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
