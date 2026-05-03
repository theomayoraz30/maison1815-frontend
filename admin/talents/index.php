<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
$pageTitle  = 'Talents';
$activePage = 'talents';

// ── AJAX/POST handlers ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Toggle active via AJAX
    if ($action === 'toggle') {
        header('Content-Type: application/json');
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false,'error'=>'Invalid CSRF']); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE talents SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        $val = (int)$pdo->prepare("SELECT is_active FROM talents WHERE id=?")->execute([$id]) ? $pdo->query("SELECT is_active FROM talents WHERE id=$id")->fetchColumn() : 0;
        $st = $pdo->prepare("SELECT is_active FROM talents WHERE id=?"); $st->execute([$id]);
        echo json_encode(['success'=>true,'is_active'=>(int)$st->fetchColumn()]); exit;
    }

    // Reorder via AJAX
    if ($action === 'reorder') {
        header('Content-Type: application/json');
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false]); exit; }
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $st  = $pdo->prepare("UPDATE talents SET sort_order=? WHERE id=?");
        foreach ($ids as $pos => $id) $st->execute([(int)$pos,(int)$id]);
        echo json_encode(['success'=>true]); exit;
    }

    // Add talent
    if ($action === 'add_talent') {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('/admin/talents/index.php'); }
        $fn = sanitize_input($_POST['first_name'] ?? '');
        $ln = sanitize_input($_POST['last_name']  ?? '');
        $ph = trim($_POST['photo'] ?? '');
        $ac = isset($_POST['is_active']) ? 1 : 0;
        $so = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM talents")->fetchColumn();
        $pdo->prepare("INSERT INTO talents (first_name,last_name,photo,is_active,sort_order) VALUES (?,?,?,?,?)")
            ->execute([$fn,$ln,$ph,$ac,$so]);
        flash('success',"Talent $fn $ln added.");
        redirect('/admin/talents/index.php');
    }

    // Edit talent
    if ($action === 'edit_talent') {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('/admin/talents/index.php'); }
        $id = (int)($_POST['id'] ?? 0);
        $fn = sanitize_input($_POST['first_name'] ?? '');
        $ln = sanitize_input($_POST['last_name']  ?? '');
        $ph = trim($_POST['photo'] ?? '');
        $ac = isset($_POST['is_active']) ? 1 : 0;
        $oldStmt = $pdo->prepare("SELECT photo FROM talents WHERE id=?"); $oldStmt->execute([$id]);
        $oldPhoto = (string)($oldStmt->fetchColumn() ?: '');
        if ($ph === '') {
            // No new photo — keep existing
            $ph = $oldPhoto;
        } elseif ($oldPhoto !== '' && $ph !== $oldPhoto) {
            // New photo uploaded — delete old file
            delete_file(BASE_PATH . $oldPhoto);
        }
        $pdo->prepare("UPDATE talents SET first_name=?,last_name=?,photo=?,is_active=? WHERE id=?")
            ->execute([$fn,$ln,$ph,$ac,$id]);
        flash('success','Talent updated.');
        redirect('/admin/talents/index.php');
    }

    // Delete talent
    if ($action === 'delete_talent') {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('/admin/talents/index.php'); }
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("SELECT photo FROM talents WHERE id=?"); $st->execute([$id]);
        $row = $st->fetch();
        if ($row && $row['photo']) delete_file(BASE_PATH . $row['photo']);
        $pdo->prepare("DELETE FROM talents WHERE id=?")->execute([$id]);
        flash('success','Talent deleted.');
        redirect('/admin/talents/index.php');
    }
}

// ── Fetch ──────────────────────────────────────────────────────────────────────
$talents = $pdo->query("SELECT * FROM talents ORDER BY sort_order ASC, created_at DESC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">

<!-- ── Header bar ─────────────────────────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
  <div></div>
  <button onclick="openTalentModal()"
          style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#FF5500;color:#fff;border:none;border-radius:3px;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;font-family:inherit;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Talent
  </button>
</div>

<!-- ── Talents table ──────────────────────────────────────────────────────── -->
<?php if (empty($talents)): ?>
<div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:4px;padding:48px;text-align:center;">
  <p style="color:#888;font-size:14px;margin:0 0 16px;">No talents yet.</p>
  <button onclick="openTalentModal()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#FF5500;color:#fff;border:none;border-radius:3px;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;font-family:inherit;">+ Add First Talent</button>
</div>
<?php else: ?>
<div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:4px;overflow:hidden;margin-bottom:24px;">
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr style="border-bottom:1px solid #2a2a2a;">
        <th style="padding:12px 16px;text-align:left;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;width:70px;">Photo</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;">Name</th>
        <th style="padding:12px 16px;text-align:center;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;width:90px;">Status</th>
        <th style="padding:12px 16px;text-align:right;font-size:11px;color:#888;letter-spacing:.08em;text-transform:uppercase;width:100px;">Actions</th>
      </tr>
    </thead>
    <tbody id="talents-list">
      <?php foreach ($talents as $t): ?>
      <tr data-id="<?= (int)$t['id'] ?>" draggable="true"
          style="border-bottom:1px solid #2a2a2a;cursor:grab;transition:background .1s;"
          onmouseover="this.style.background='#222'" onmouseout="this.style.background='transparent'">
        <td style="padding:12px 16px;">
          <?php if (!empty($t['photo'])): ?>
            <img src="<?= htmlspecialchars(BASE_URL."/uploads/".$t['photo'],ENT_QUOTES,'UTF-8') ?>"
                 style="width:44px;height:44px;border-radius:50%;object-fit:cover;display:block;background:#111;">
          <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:#111;border:1px solid #2a2a2a;display:flex;align-items:center;justify-content:center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </div>
          <?php endif; ?>
        </td>
        <td style="padding:12px 16px;color:#fff;font-size:13px;font-weight:500;">
          <?= htmlspecialchars($t['first_name'].' '.$t['last_name'],ENT_QUOTES,'UTF-8') ?>
        </td>
        <td style="padding:12px 16px;text-align:center;">
          <label style="position:relative;display:inline-block;width:40px;height:22px;cursor:pointer;">
            <input type="checkbox" <?= $t['is_active'] ? 'checked' : '' ?>
                   onchange="toggleActive(<?= (int)$t['id'] ?>,this)"
                   style="opacity:0;width:0;height:0;position:absolute;">
            <span class="tog" style="position:absolute;inset:0;border-radius:11px;background:<?= $t['is_active'] ? '#FF5500' : '#2a2a2a' ?>;transition:background .2s;">
              <span style="position:absolute;top:3px;left:<?= $t['is_active'] ? '21px' : '3px' ?>;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .2s;"></span>
            </span>
          </label>
        </td>
        <td style="padding:12px 16px;text-align:right;">
          <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
            <button type="button" onclick='openTalentModal(<?= htmlspecialchars(json_encode($t),ENT_QUOTES,"UTF-8") ?>)'
                    style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#888;cursor:pointer;transition:border-color .15s,color .15s;"
                    onmouseover="this.style.borderColor='#FF5500';this.style.color='#FF5500';" onmouseout="this.style.borderColor='#2a2a2a';this.style.color='#888';">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this talent?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">
              <input type="hidden" name="action" value="delete_talent">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
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
</div>


<?php endif; ?>

<!-- ── Add/Edit Modal ──────────────────────────────────────────────────────── -->
<div id="talent-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:100;align-items:center;justify-content:center;">
  <div style="background:#1a1a1a;border:1px solid #2a2a2a;padding:32px;border-radius:4px;max-width:440px;width:100%;margin:24px 16px;">
    <h3 id="tm-title" style="color:#fff;margin:0 0 24px;font-size:16px;font-weight:600;">Add Talent</h3>
    <form method="POST" id="talent-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">
      <input type="hidden" name="action" id="tm-action" value="add_talent">
      <input type="hidden" name="id"     id="tm-id"     value="">
      <input type="hidden" name="photo"  id="tm-photo"  value="">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
          <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">First Name *</label>
          <input name="first_name" id="tm-fn" required
                 style="width:100%;padding:10px 12px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#fff;font-family:inherit;font-size:13px;outline:none;box-sizing:border-box;"
                 onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Last Name *</label>
          <input name="last_name" id="tm-ln" required
                 style="width:100%;padding:10px 12px;background:#111;border:1px solid #2a2a2a;border-radius:3px;color:#fff;font-family:inherit;font-size:13px;outline:none;box-sizing:border-box;"
                 onfocus="this.style.borderColor='#FF5500'" onblur="this.style.borderColor='#2a2a2a'">
        </div>
      </div>

      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:11px;color:#888;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Photo</label>
        <div id="tm-photo-preview" style="margin-bottom:8px;"></div>
        <label style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;background:#111;border:1px solid #2a2a2a;border-radius:3px;cursor:pointer;font-size:12px;color:#888;letter-spacing:.06em;text-transform:uppercase;">
          Upload Photo
          <input type="file" id="tm-photo-input" accept="image/*" style="display:none;">
        </label>
        <span id="tm-photo-status" style="font-size:12px;color:#888;margin-left:10px;"></span>
      </div>

      <div style="margin-bottom:24px;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;color:#ccc;">
          <input type="checkbox" name="is_active" id="tm-active" value="1" checked
                 style="width:16px;height:16px;accent-color:#FF5500;">
          Active (visible on public site)
        </label>
      </div>

      <div style="display:flex;gap:12px;">
        <button type="button" onclick="closeTalentModal()"
                style="flex:1;padding:10px;background:#111;border:1px solid #2a2a2a;color:#888;border-radius:3px;cursor:pointer;font-family:inherit;font-size:13px;">Cancel</button>
        <button type="submit"
                style="flex:1;padding:10px;background:#FF5500;border:none;color:#fff;border-radius:3px;cursor:pointer;font-family:inherit;font-size:13px;font-weight:600;">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function openTalentModal(data) {
  document.getElementById('tm-title').textContent  = data ? 'Edit Talent' : 'Add Talent';
  document.getElementById('tm-action').value       = data ? 'edit_talent' : 'add_talent';
  document.getElementById('tm-id').value           = data ? data.id : '';
  document.getElementById('tm-fn').value           = data ? data.first_name : '';
  document.getElementById('tm-ln').value           = data ? data.last_name  : '';
  document.getElementById('tm-photo').value        = data ? (data.photo||'') : '';
  document.getElementById('tm-active').checked     = data ? !!parseInt(data.is_active) : true;
  const prev = document.getElementById('tm-photo-preview');
  prev.innerHTML = (data && data.photo)
    ? '<img src="<?= BASE_URL ?>/uploads/'+data.photo+'" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;">'
    : '';
  document.getElementById('talent-modal').style.display = 'flex';
}
function closeTalentModal() { document.getElementById('talent-modal').style.display = 'none'; }
document.getElementById('talent-modal').addEventListener('click', function(e){ if(e.target===this) closeTalentModal(); });

document.getElementById('tm-photo-input').addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    document.getElementById('tm-photo-preview').innerHTML = 
      `<img src="${e.target.result}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;opacity:0.6;filter:grayscale(100%);transition:opacity 0.2s;">`;
  };
  reader.readAsDataURL(file);

  const st = document.getElementById('tm-photo-status');
  st.textContent = 'Uploading…'; st.style.color = '#888';
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('upload_dir', 'talents');
  fd.append('image', file);
  const r = await fetch('/admin/upload/image.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.success) {
    const path = d.path.replace(/^\/uploads\//,'');
    document.getElementById('tm-photo').value = path;
    document.getElementById('tm-photo-preview').innerHTML = '<img src="<?= BASE_URL ?>/uploads/'+path+'" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;">';
    st.textContent = '✓'; st.style.color = '#22c55e';
  } else { st.textContent = 'Error'; st.style.color = '#ef4444'; }
});

async function toggleActive(id, cb) {
  const fd = new FormData(); fd.append('action','toggle'); fd.append('csrf_token',CSRF); fd.append('id',id);
  const tog = cb.closest('label').querySelector('.tog');
  const knob = tog ? tog.querySelector('span') : null;
  const r = await fetch('/admin/talents/index.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.success) {
    const on = d.is_active===1;
    if(tog){ tog.style.background=on?'#FF5500':'#2a2a2a'; }
    if(knob){ knob.style.left=on?'21px':'3px'; }
  } else { cb.checked=!cb.checked; }
}

// Drag & drop reorder
const list = document.getElementById('talents-list');
if (list) {
  let src=null;
  list.querySelectorAll('tr[draggable]').forEach(function(row){
    row.addEventListener('dragstart',function(e){src=row;row.style.opacity='.4';e.dataTransfer.effectAllowed='move';});
    row.addEventListener('dragend',  function(){row.style.opacity='1';});
    row.addEventListener('dragover', function(e){e.preventDefault();row.style.background='#1f1f1f';});
    row.addEventListener('dragleave',function(){row.style.background='transparent';});
    row.addEventListener('drop',     function(e){
      e.preventDefault();row.style.background='transparent';
      if(src&&src!==row){const rows=[...list.querySelectorAll('tr[data-id]')];if(rows.indexOf(src)<rows.indexOf(row))row.after(src);else row.before(src); saveOrder();}
    });
  });
}

async function saveOrder() {
  const ids=[...document.querySelectorAll('#talents-list tr[data-id]')].map(r=>parseInt(r.dataset.id));
  const fd=new FormData();fd.append('action','reorder');fd.append('csrf_token',CSRF);fd.append('ids',JSON.stringify(ids));
  await fetch('/admin/talents/index.php',{method:'POST',body:fd}).catch(()=>console.error('Failed to save order'));
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>