<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
$pageTitle  = 'About Page';
$activePage = 'about';

// ── AJAX handlers ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save about image
    if ($action === 'save_image') {
        header('Content-Type: application/json');
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false,'error'=>'Invalid CSRF']); exit; }
        $path = trim($_POST['image_path'] ?? '');
        // Delete old about image before replacing
        $oldRow = $pdo->query("SELECT image_path FROM about_page WHERE id=1")->fetch();
        if ($oldRow && !empty($oldRow['image_path']) && $oldRow['image_path'] !== $path) {
            delete_file(BASE_PATH . $oldRow['image_path']);
        }
        $pdo->prepare("UPDATE about_page SET image_path=? WHERE id=1")->execute([$path]);
        echo json_encode(['success'=>true]); exit;
    }

    // Add team member
    if ($action === 'add_member') {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('/admin/about/index.php'); }
        $fn   = sanitize_input($_POST['first_name'] ?? '');
        $ln   = sanitize_input($_POST['last_name']  ?? '');
        $rfr  = sanitize_input($_POST['role_fr']    ?? '');
        $rde  = sanitize_input($_POST['role_de']    ?? '');
        $ren  = sanitize_input($_POST['role_en']    ?? '');
        $em   = sanitize_input($_POST['email']      ?? '');
        $ph   = trim($_POST['photo'] ?? '');
        $so   = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM team_members")->fetchColumn();
        $pdo->prepare("INSERT INTO team_members (first_name,last_name,role_fr,role_de,role_en,email,photo,sort_order) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$fn,$ln,$rfr,$rde,$ren,$em,$ph,$so]);
        flash('success',"Team member $fn $ln added.");
        redirect('/admin/about/index.php');
    }

    // Edit team member
    if ($action === 'edit_member') {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('/admin/about/index.php'); }
        $id  = (int)($_POST['id'] ?? 0);
        $fn  = sanitize_input($_POST['first_name'] ?? '');
        $ln  = sanitize_input($_POST['last_name']  ?? '');
        $rfr = sanitize_input($_POST['role_fr']    ?? '');
        $rde = sanitize_input($_POST['role_de']    ?? '');
        $ren = sanitize_input($_POST['role_en']    ?? '');
        $em  = sanitize_input($_POST['email']      ?? '');
        $ph  = trim($_POST['photo'] ?? '');
        // Delete old photo if replaced
        $oldM = $pdo->prepare("SELECT photo FROM team_members WHERE id=?"); $oldM->execute([$id]);
        $oldPhoto = (string)($oldM->fetchColumn() ?: '');
        if ($ph !== '' && $oldPhoto !== '' && $ph !== $oldPhoto) {
            delete_file(BASE_PATH . $oldPhoto);
        } elseif ($ph === '') {
            $ph = $oldPhoto; // keep old if no new upload
        }
        $pdo->prepare("UPDATE team_members SET first_name=?,last_name=?,role_fr=?,role_de=?,role_en=?,email=?,photo=? WHERE id=?")
            ->execute([$fn,$ln,$rfr,$rde,$ren,$em,$ph,$id]);
        flash('success',"Member updated.");
        redirect('/admin/about/index.php');
    }

    // Delete team member
    if ($action === 'delete_member') {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('/admin/about/index.php'); }
        $id = (int)($_POST['id'] ?? 0);
        $m  = $pdo->prepare("SELECT photo FROM team_members WHERE id=?"); $m->execute([$id]); $row = $m->fetch();
        if ($row && $row['photo']) delete_file(BASE_PATH . $row['photo']);
        $pdo->prepare("DELETE FROM team_members WHERE id=?")->execute([$id]);
        flash('success','Member deleted.');
        redirect('/admin/about/index.php');
    }

    // Reorder
    if ($action === 'reorder') {
        header('Content-Type: application/json');
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false]); exit; }
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $st  = $pdo->prepare("UPDATE team_members SET sort_order=? WHERE id=?");
        foreach ($ids as $pos => $id) $st->execute([(int)$pos, (int)$id]);
        echo json_encode(['success'=>true]); exit;
    }
}

// ── Fetch data ─────────────────────────────────────────────────────────────────
$aboutPage = $pdo->query("SELECT * FROM about_page WHERE id=1")->fetch();
$members   = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">

<!-- ── About Image ─────────────────────────────────────────────────────────── -->
<div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:4px;padding:24px;margin-bottom:28px;">
  <p style="font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;margin:0 0 16px;font-weight:500;">About Page Image</p>
  <div id="about-img-preview-container">
  <?php if (!empty($aboutPage['image_path'])): ?>
    <img src="<?= htmlspecialchars(BASE_URL."/uploads/".$aboutPage['image_path'],ENT_QUOTES,'UTF-8') ?>"
         style="max-width:100%;height:200px;object-fit:cover;border-radius:3px;margin-bottom:16px;display:block;">
  <?php endif; ?>
  </div>
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <label style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:#111;border:1px solid #2a2a2a;border-radius:3px;cursor:pointer;font-size:12px;color:#888;letter-spacing:.06em;text-transform:uppercase;transition:border-color .15s;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Upload Image
      <input type="file" id="about-img-input" accept="image/*" style="display:none;">
    </label>
    <span id="about-img-status" style="font-size:12px;color:#888;"></span>
  </div>
</div>

<!-- ── Team Members ────────────────────────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
  <p style="font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;margin:0;font-weight:500;">Team Members (<?= count($members) ?>)</p>
  <button onclick="openMemberModal()"
          style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#FF5500;color:#fff;border:none;border-radius:3px;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;font-family:inherit;">
    + Add Member
  </button>
</div>

<!-- Members list -->
<div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:4px;overflow:hidden;margin-bottom:24px;">
<?php if (empty($members)): ?>
  <p style="text-align:center;color:#888;padding:32px;margin:0;">No team members yet.</p>
<?php else: ?>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr style="border-bottom:1px solid #2a2a2a;">
        <th style="padding:12px 16px;text-align:left;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;width:60px;"></th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;">Name</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;">Role (EN)</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;">Email</th>
        <th style="padding:12px 16px;text-align:right;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;width:90px;">Actions</th>
      </tr>
    </thead>
    <tbody id="members-list">
      <?php foreach ($members as $m): ?>
      <tr data-id="<?= (int)$m['id'] ?>" draggable="true"
          style="border-bottom:1px solid #2a2a2a;cursor:grab;transition:background .1s;"
          onmouseover="this.style.background='#222'" onmouseout="this.style.background='transparent'">
        <td style="padding:12px 16px;">
          <?php if (!empty($m['photo'])): ?>
            <img src="<?= htmlspecialchars(BASE_URL."/uploads/".$m['photo'],ENT_QUOTES,'UTF-8') ?>"
                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;display:block;background:#111;">
          <?php else: ?>
            <div style="width:36px;height:36px;border-radius:50%;background:#111;border:1px solid #2a2a2a;display:flex;align-items:center;justify-content:center;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </div>
          <?php endif; ?>
        </td>
        <td style="padding:12px 16px;color:#fff;font-size:13px;font-weight:500;">
          <?= htmlspecialchars($m['first_name'].' '.$m['last_name'],ENT_QUOTES,'UTF-8') ?>
        </td>
        <td style="padding:12px 16px;color:#ccc;font-size:13px;"><?= htmlspecialchars($m['role_en'],ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:12px 16px;color:#888;font-size:12px;"><?= htmlspecialchars($m['email'],ENT_QUOTES,'UTF-8') ?></td>
        <td style="padding:12px 16px;text-align:right;">
          <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
            <button type="button" onclick='openMemberModal(<?= htmlspecialchars(json_encode($m),ENT_QUOTES,"UTF-8") ?>)'
                    style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#888;cursor:pointer;transition:border-color .15s,color .15s;"
                    onmouseover="this.style.borderColor='#FF5500';this.style.color='#FF5500';" onmouseout="this.style.borderColor='#2a2a2a';this.style.color='#888';">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this member?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">
              <input type="hidden" name="action" value="delete_member">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button type="submit"
                      style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#888;cursor:pointer;transition:border-color .15s,color .15s;"
                      onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444';" onmouseout="this.style.borderColor='#2a2a2a';this.style.color='#888';">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>



<!-- ── Add/Edit Modal ──────────────────────────────────────────────────────── -->
<div id="member-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:100;align-items:center;justify-content:center;overflow-y:auto;">
  <div style="background:#1a1a1a;border:1px solid #2a2a2a;padding:32px;border-radius:4px;max-width:560px;width:100%;margin:24px 16px;">
    <h3 id="modal-title" style="color:#fff;margin:0 0 24px;font-size:16px;font-weight:600;">Add Team Member</h3>
    <form method="POST" id="member-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">
      <input type="hidden" name="action"     id="m-action" value="add_member">
      <input type="hidden" name="id"         id="m-id"     value="">
      <input type="hidden" name="photo"      id="m-photo"  value="">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
          <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">First Name *</label>
          <input name="first_name" id="m-fn" required
                 style="width:100%;padding:10px 12px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#fff;font-family:inherit;font-size:13px;outline:none;box-sizing:border-box;"
                 onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Last Name *</label>
          <input name="last_name" id="m-ln" required
                 style="width:100%;padding:10px 12px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#fff;font-family:inherit;font-size:13px;outline:none;box-sizing:border-box;"
                 onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
        <?php foreach ([['fr','FR'],['de','DE'],['en','EN']] as [$code,$label]): ?>
        <div>
          <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Role <?= $label ?></label>
          <input name="role_<?= $code ?>" id="m-r<?= $code ?>"
                 style="width:100%;padding:10px 12px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#fff;font-family:inherit;font-size:13px;outline:none;box-sizing:border-box;"
                 onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Email</label>
        <input name="email" id="m-em" type="email"
               style="width:100%;padding:10px 12px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#fff;font-family:inherit;font-size:13px;outline:none;box-sizing:border-box;"
               onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
      </div>

      <div style="margin-bottom:24px;">
        <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Profile Photo</label>
        <div id="m-photo-preview" style="margin-bottom:8px;"></div>
        <label style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;background:#111;border:1px solid #2a2a2a;border-radius:3px;cursor:pointer;font-size:12px;color:#888;letter-spacing:.06em;text-transform:uppercase;">
          Upload Photo
          <input type="file" id="m-photo-input" accept="image/*" style="display:none;">
        </label>
        <span id="m-photo-status" style="font-size:12px;color:#888;margin-left:10px;"></span>
      </div>

      <div style="display:flex;gap:12px;">
        <button type="button" onclick="closeMemberModal()"
                style="flex:1;padding:10px;background:#111;border:1px solid #2a2a2a;color:#888;border-radius:3px;cursor:pointer;font-family:inherit;font-size:13px;">Cancel</button>
        <button type="submit"
                style="flex:1;padding:10px;background:#FF5500;border:none;color:#fff;border-radius:3px;cursor:pointer;font-family:inherit;font-size:13px;font-weight:600;">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── About image upload ─────────────────────────────────────────────────────────
document.getElementById('about-img-input').addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    document.getElementById('about-img-preview-container').innerHTML = 
      `<img src="${e.target.result}" style="max-width:100%;height:200px;object-fit:cover;border-radius:3px;margin-bottom:16px;display:block;opacity:0.6;filter:grayscale(100%);transition:opacity 0.2s;">`;
  };
  reader.readAsDataURL(file);

  const st = document.getElementById('about-img-status');
  st.textContent = 'Uploading…'; st.style.color = '#888';
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('upload_dir', 'about');
  fd.append('image', file);
  const r = await fetch('/admin/upload/image.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.success) {
    // Save path to DB
    const fd2 = new FormData();
    fd2.append('action','save_image'); fd2.append('csrf_token',CSRF); fd2.append('image_path',d.path.replace(/^\/uploads\//,''));
    await fetch('/admin/about/index.php', {method:'POST', body:fd2});
    st.textContent = 'Saved ✓'; st.style.color = '#22c55e';
    setTimeout(()=>location.reload(), 800);
  } else { st.textContent = 'Error: '+d.error; st.style.color = '#ef4444'; }
});

// ── Member modal ───────────────────────────────────────────────────────────────
function openMemberModal(data) {
  const modal = document.getElementById('member-modal');
  document.getElementById('modal-title').textContent = data ? 'Edit Team Member' : 'Add Team Member';
  document.getElementById('m-action').value = data ? 'edit_member' : 'add_member';
  document.getElementById('m-id').value     = data ? data.id : '';
  document.getElementById('m-fn').value     = data ? data.first_name : '';
  document.getElementById('m-ln').value     = data ? data.last_name  : '';
  document.getElementById('m-rfr').value    = data ? data.role_fr : '';
  document.getElementById('m-rde').value    = data ? data.role_de : '';
  document.getElementById('m-ren').value    = data ? data.role_en : '';
  document.getElementById('m-em').value     = data ? data.email  : '';
  document.getElementById('m-photo').value  = data ? (data.photo||'') : '';
  const prev = document.getElementById('m-photo-preview');
  prev.innerHTML = (data && data.photo)
    ? '<img src="<?= BASE_URL ?>/uploads/'+data.photo+'" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;">'
    : '';
  modal.style.display = 'flex';
}
function closeMemberModal() { document.getElementById('member-modal').style.display = 'none'; }
document.getElementById('member-modal').addEventListener('click', function(e) { if(e.target===this) closeMemberModal(); });

// Photo upload in modal
document.getElementById('m-photo-input').addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    document.getElementById('m-photo-preview').innerHTML = 
      `<img src="${e.target.result}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;opacity:0.6;filter:grayscale(100%);transition:opacity 0.2s;">`;
  };
  reader.readAsDataURL(file);

  const st = document.getElementById('m-photo-status');
  st.textContent = 'Uploading…';
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('upload_dir', 'team');
  fd.append('image', file);
  const r = await fetch('/admin/upload/image.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.success) {
    const path = d.path.replace(/^\/uploads\//,'');
    document.getElementById('m-photo').value = path;
    document.getElementById('m-photo-preview').innerHTML = '<img src="<?= BASE_URL ?>/uploads/'+path+'" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;">';
    st.textContent = '✓'; st.style.color = '#22c55e';
  } else { st.textContent = 'Error'; st.style.color = '#ef4444'; }
});

// ── Drag & drop reorder ────────────────────────────────────────────────────────
const list = document.getElementById('members-list');
if (list) {
  let src = null;
  list.querySelectorAll('tr[draggable]').forEach(function(row) {
    row.addEventListener('dragstart', function(e) { src=row; row.style.opacity='.4'; e.dataTransfer.effectAllowed='move'; });
    row.addEventListener('dragend',   function()  { row.style.opacity='1'; });
    row.addEventListener('dragover',  function(e) { e.preventDefault(); row.style.background='#1f1f1f'; });
    row.addEventListener('dragleave', function()  { row.style.background='transparent'; });
    row.addEventListener('drop',      function(e) {
      e.preventDefault(); row.style.background='transparent';
      if (src && src!==row) {
        const rows=[...list.querySelectorAll('tr[data-id]')];
        if (rows.indexOf(src)<rows.indexOf(row)) row.after(src); else row.before(src);
        saveOrder();
      }
    });
  });
}

async function saveOrder() {
  const ids = [...document.querySelectorAll('#members-list tr[data-id]')].map(r=>parseInt(r.dataset.id));
  const fd  = new FormData(); fd.append('action','reorder'); fd.append('csrf_token',CSRF); fd.append('ids',JSON.stringify(ids));
  await fetch('/admin/about/index.php',{method:'POST',body:fd}).catch(()=>console.error('Failed to save order'));
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>