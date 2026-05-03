<?php
/**
 * admin/index.php — Dashboard home page.
 *
 * Displays high-level stats and a combined list of the 5 most recently
 * added projects (video + photo).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// ── Stats ────────────────────────────────────────────────────────────────────

$stats = [];

try {
    $stats['video_projects'] = (int) $pdo->query("SELECT COUNT(*) FROM `video_projects`")->fetchColumn();
    $stats['photo_projects'] = (int) $pdo->query("SELECT COUNT(*) FROM `photo_projects`")->fetchColumn();
    $stats['talents']        = (int) $pdo->query("SELECT COUNT(*) FROM `talents`")->fetchColumn();
    $stats['team_members']   = (int) $pdo->query("SELECT COUNT(*) FROM `team_members`")->fetchColumn();
} catch (PDOException $e) {
    error_log('[Maison1815] Dashboard stats error: ' . $e->getMessage());
    $stats = ['video_projects' => 0, 'photo_projects' => 0, 'talents' => 0, 'team_members' => 0];
}

// ── Recent projects (combined video + photo, latest 5) ──────────────────────

$recentProjects = [];

try {
    $videoRows = $pdo->query(
        "SELECT `id`, `title`, `client`, 'video' AS `type`, `is_active`, `created_at`
         FROM `video_projects`
         ORDER BY `created_at` DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    $photoRows = $pdo->query(
        "SELECT `id`, `title`, `client`, 'photo' AS `type`, `is_active`, `created_at`
         FROM `photo_projects`
         ORDER BY `created_at` DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    $recentProjects = array_merge($videoRows, $photoRows);

    // Sort combined list by created_at descending, take top 5
    usort($recentProjects, static function (array $a, array $b): int {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    $recentProjects = array_slice($recentProjects, 0, 5);

} catch (PDOException $e) {
    error_log('[Maison1815] Dashboard recent projects error: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ════════════════════════════════════════════════════
     STATS CARDS GRID
════════════════════════════════════════════════════ -->
<div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; margin-bottom:40px;">
<?php

$statCards = [
    [
        'label'  => 'Video Projects',
        'value'  => $stats['video_projects'],
        'link'   => '/admin/projects/video/index.php',
        'color'  => '#FF5500',
        'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
    ],
    [
        'label'  => 'Photo Projects',
        'value'  => $stats['photo_projects'],
        'link'   => '/admin/projects/photo/index.php',
        'color'  => '#3b82f6',
        'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
    ],
    [
        'label'  => 'Talents',
        'value'  => $stats['talents'],
        'link'   => '/admin/talents/index.php',
        'color'  => '#a855f7',
        'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    ],
    [
        'label'  => 'Team Members',
        'value'  => $stats['team_members'],
        'link'   => '/admin/about/index.php',
        'color'  => '#22c55e',
        'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    ],
];

foreach ($statCards as $card): ?>
    <a href="<?= htmlspecialchars($card['link'], ENT_QUOTES, 'UTF-8') ?>"
       style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; padding:24px; text-decoration:none; display:block; position:relative; overflow:hidden; transition:border-color 0.2s, background 0.2s;"
       onmouseover="this.style.borderColor='#3a3a3a'; this.style.background='#1e1e1e';"
       onmouseout="this.style.borderColor='#2a2a2a'; this.style.background='#1a1a1a';">

        <!-- Icon badge top-right -->
        <div style="position:absolute; top:20px; right:20px; color:<?= $card['color'] ?>; opacity:0.7;">
            <?= $card['icon'] ?>
        </div>

        <!-- Big number -->
        <div style="font-size:52px; font-weight:700; color:#ffffff; line-height:1; margin-bottom:8px; letter-spacing:-0.02em;">
            <?= number_format($card['value']) ?>
        </div>

        <!-- Label -->
        <div style="font-size:12px; color:#888888; text-transform:uppercase; letter-spacing:0.08em; font-weight:500;">
            <?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <!-- Accent bottom bar -->
        <div style="position:absolute; bottom:0; left:0; width:3px; height:100%; background:<?= $card['color'] ?>; opacity:0.6;"></div>
    </a>
<?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════════════
     RECENT PROJECTS TABLE
════════════════════════════════════════════════════ -->
<div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:4px; overflow:hidden;">

    <!-- Section header -->
    <div style="padding:18px 24px; border-bottom:1px solid #2a2a2a; display:flex; align-items:center; justify-content:space-between;">
        <h2 style="font-size:13px; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em; margin:0;">
            Recent Projects
        </h2>
        <div style="display:flex; gap:8px;">
            <a href="/admin/projects/video/index.php"
               style="font-size:11px; color:#FF5500; text-decoration:none; letter-spacing:0.05em; text-transform:uppercase; padding:5px 10px; border:1px solid #FF550033; border-radius:2px; transition:background 0.15s;"
               onmouseover="this.style.background='#FF550015'"
               onmouseout="this.style.background='transparent'">
                + Video
            </a>
            <a href="/admin/projects/photo/index.php"
               style="font-size:11px; color:#3b82f6; text-decoration:none; letter-spacing:0.05em; text-transform:uppercase; padding:5px 10px; border:1px solid #3b82f633; border-radius:2px; transition:background 0.15s;"
               onmouseover="this.style.background='#3b82f615'"
               onmouseout="this.style.background='transparent'">
                + Photo
            </a>
        </div>
    </div>

    <?php if (empty($recentProjects)): ?>
        <!-- Empty state -->
        <div style="padding:60px 24px; text-align:center;">
            <div style="color:#444; margin-bottom:12px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
            </div>
            <p style="color:#888888; font-size:14px; margin:0;">No projects yet.</p>
            <p style="color:#555555; font-size:12px; margin:6px 0 0; letter-spacing:0.04em; text-transform:uppercase;">
                Add your first video or photo project to get started.
            </p>
        </div>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#111111;">
                    <th style="padding:11px 24px; text-align:left; font-size:11px; font-weight:600; color:#888888; text-transform:uppercase; letter-spacing:0.1em; border-bottom:1px solid #2a2a2a; white-space:nowrap;">Type</th>
                    <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:600; color:#888888; text-transform:uppercase; letter-spacing:0.1em; border-bottom:1px solid #2a2a2a;">Title</th>
                    <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:600; color:#888888; text-transform:uppercase; letter-spacing:0.1em; border-bottom:1px solid #2a2a2a;">Client</th>
                    <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:600; color:#888888; text-transform:uppercase; letter-spacing:0.1em; border-bottom:1px solid #2a2a2a; white-space:nowrap;">Status</th>
                    <th style="padding:11px 24px 11px 16px; text-align:right; font-size:11px; font-weight:600; color:#888888; text-transform:uppercase; letter-spacing:0.1em; border-bottom:1px solid #2a2a2a; white-space:nowrap;">Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentProjects as $i => $project):
                    $isVideo  = $project['type'] === 'video';
                    $editUrl  = $isVideo
                        ? '/admin/projects/video/edit.php?id=' . (int) $project['id']
                        : '/admin/projects/photo/edit.php?id=' . (int) $project['id'];
                    $rowBg    = $i % 2 === 0 ? '#1a1a1a' : '#1c1c1c';
                    $isActive = (bool) $project['is_active'];
                    $addedAt  = date('d M Y', strtotime($project['created_at']));
                    $titleDisplay = $project['title'] !== '' ? $project['title'] : '—';
                    $clientDisplay = $project['client'] !== '' ? $project['client'] : '—';
                ?>
                    <tr style="background:<?= $rowBg ?>; transition:background 0.12s; cursor:pointer;"
                        onmouseover="this.style.background='#222222'"
                        onmouseout="this.style.background='<?= $rowBg ?>'"
                        onclick="window.location='<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>'">

                        <!-- Type badge -->
                        <td style="padding:14px 24px; white-space:nowrap;">
                            <?php if ($isVideo): ?>
                                <span style="display:inline-flex; align-items:center; gap:5px; background:#FF550022; color:#FF5500; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; padding:3px 8px; border-radius:2px; border:1px solid #FF550044; white-space:nowrap;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                                    Video
                                </span>
                            <?php else: ?>
                                <span style="display:inline-flex; align-items:center; gap:5px; background:#3b82f622; color:#3b82f6; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; padding:3px 8px; border-radius:2px; border:1px solid #3b82f644; white-space:nowrap;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="3"/></svg>
                                    Photo
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Title -->
                        <td style="padding:14px 16px; font-size:14px; color:#ffffff; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($titleDisplay, ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <!-- Client -->
                        <td style="padding:14px 16px; font-size:13px; color:#888888; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($clientDisplay, ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <!-- Active / Inactive -->
                        <td style="padding:14px 16px; white-space:nowrap;">
                            <?php if ($isActive): ?>
                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#22c55e; letter-spacing:0.05em; text-transform:uppercase;">
                                    <span style="width:6px; height:6px; background:#22c55e; border-radius:50%; display:inline-block; flex-shrink:0;"></span>
                                    Active
                                </span>
                            <?php else: ?>
                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#555555; letter-spacing:0.05em; text-transform:uppercase;">
                                    <span style="width:6px; height:6px; background:#555555; border-radius:50%; display:inline-block; flex-shrink:0;"></span>
                                    Draft
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Date -->
                        <td style="padding:14px 24px 14px 16px; text-align:right; font-size:12px; color:#555555; white-space:nowrap; font-variant-numeric:tabular-nums;">
                            <?= htmlspecialchars($addedAt, ENT_QUOTES, 'UTF-8') ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Footer link -->
        <div style="padding:14px 24px; border-top:1px solid #2a2a2a; display:flex; gap:16px; justify-content:flex-end;">
            <a href="/admin/projects/video/index.php"
               style="font-size:12px; color:#888888; text-decoration:none; letter-spacing:0.04em; transition:color 0.15s;"
               onmouseover="this.style.color='#ffffff'"
               onmouseout="this.style.color='#888888'">
                All video projects →
            </a>
            <a href="/admin/projects/photo/index.php"
               style="font-size:12px; color:#888888; text-decoration:none; letter-spacing:0.04em; transition:color 0.15s;"
               onmouseover="this.style.color='#ffffff'"
               onmouseout="this.style.color='#888888'">
                All photo projects →
            </a>
        </div>

    <?php endif; ?>

</div>
<!-- END recent projects -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
