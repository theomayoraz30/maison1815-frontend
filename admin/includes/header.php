<?php
/**
 * header.php — Global admin layout header.
 *
 * Expected variables set by the including page before require_once:
 *   string $pageTitle  — shown in <title> and the H1 breadcrumb
 *   string $activePage — one of: 'dashboard' | 'video-projects' | 'photo-projects' | 'about' | 'talents'
 */

declare(strict_types=1);

// Session should already be started by auth.php, but guard defensively.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle  = $pageTitle  ?? 'Admin';
$activePage = $activePage ?? '';

$adminUsername = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');

// Nav item helper — returns inline style string for active/inactive state.
$navItemStyle = function (string $page) use ($activePage): string {
    $base = 'display:flex; align-items:center; gap:12px; padding:12px 20px; text-decoration:none; font-size:13px; letter-spacing:0.05em; text-transform:uppercase; font-weight:500; transition:color 0.15s, background 0.15s; border-left:3px solid transparent;';
    if ($activePage === $page) {
        return $base . 'color:#ffffff; background:#1a1a1a; border-left-color:#FF5500;';
    }
    return $base . 'color:#888888;';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Maison 1815 Admin</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            DEFAULT: '#FF5500',
                            500: '#FF5500',
                            600: '#e64d00',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* SF Pro Display font-face declarations */
        @font-face {
            font-family: 'SF Pro Display';
            src: url('/sf-pro-display/SFPRODISPLAYREGULAR.OTF') format('opentype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'SF Pro Display';
            src: url('/sf-pro-display/SFPRODISPLAYMEDIUM.OTF') format('opentype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'SF Pro Display';
            src: url('/sf-pro-display/SFPRODISPLAYBOLD.OTF') format('opentype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #000000;
            color: #ffffff;
            margin: 0;
        }

        /* Sidebar nav hover — only applies to inactive items */
        .nav-item:not(.nav-active):hover {
            color: #ffffff !important;
            background: #1a1a1a !important;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #3a3a3a; }

        /* Flash message transition */
        .flash-message {
            animation: flash-in 0.25s ease-out;
        }
        @keyframes flash-in {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body style="background:#000000; color:#ffffff;">

<div style="display:flex; min-height:100vh;">

    <!-- ════════════════════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════════════════════ -->
    <aside style="width:260px; background:#111111; min-height:100vh; position:fixed; top:0; left:0; display:flex; flex-direction:column; border-right:1px solid #2a2a2a; z-index:50;">

        <!-- Logo -->
        <div style="padding:24px 24px 20px; border-bottom:1px solid #2a2a2a; flex-shrink:0;">
            <a href="/admin/index.php" style="text-decoration:none;">
                <span style="font-size:16px; font-weight:700; color:#ffffff; letter-spacing:0.15em; text-transform:uppercase; display:block;">MAISON 1815</span>
                <span style="font-size:11px; color:#888888; letter-spacing:0.08em; text-transform:uppercase; margin-top:2px; display:block;">Admin Dashboard</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav style="flex:1; padding:12px 0; overflow-y:auto;">

            <!-- Dashboard -->
            <a href="/admin/index.php"
               class="nav-item <?= $activePage === 'dashboard' ? 'nav-active' : '' ?>"
               style="<?= $navItemStyle('dashboard') ?>">
                <!-- Home icon -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <path d="M3 9L12 2l9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Dashboard
            </a>

            <!-- Video Projects -->
            <a href="/admin/projects/video/index.php"
               class="nav-item <?= $activePage === 'video-projects' ? 'nav-active' : '' ?>"
               style="<?= $navItemStyle('video-projects') ?>">
                <!-- Film/video icon -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <polygon points="23 7 16 12 23 17 23 7"/>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                </svg>
                Video Projects
            </a>

            <!-- Photo Projects -->
            <a href="/admin/projects/photo/index.php"
               class="nav-item <?= $activePage === 'photo-projects' ? 'nav-active' : '' ?>"
               style="<?= $navItemStyle('photo-projects') ?>">
                <!-- Camera icon -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                Photo Projects
            </a>

            <!-- About Page -->
            <a href="/admin/about/index.php"
               class="nav-item <?= $activePage === 'about' ? 'nav-active' : '' ?>"
               style="<?= $navItemStyle('about') ?>">
                <!-- Info icon -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                About Page
            </a>

            <!-- Talents -->
            <a href="/admin/talents/index.php"
               class="nav-item <?= $activePage === 'talents' ? 'nav-active' : '' ?>"
               style="<?= $navItemStyle('talents') ?>">
                <!-- Users icon -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Talents
            </a>

        </nav>

        <!-- Bottom: user info + logout -->
        <div style="padding:16px; border-top:1px solid #2a2a2a; flex-shrink:0;">
            <div style="font-size:12px; color:#888888; margin-bottom:10px; letter-spacing:0.03em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= $adminUsername ?>
            </div>
            <form method="POST" action="/admin/logout.php" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit"
                        style="background:none; border:none; padding:0; font-size:12px; color:#888888; cursor:pointer; font-family:inherit; letter-spacing:0.05em; text-transform:uppercase; transition:color 0.15s; display:flex; align-items:center; gap:6px;"
                        onmouseover="this.style.color='#ef4444'"
                        onmouseout="this.style.color='#888888'">
                    <!-- Logout icon -->
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Sign Out
                </button>
            </form>
        </div>

    </aside>
    <!-- END SIDEBAR -->

    <!-- ════════════════════════════════════════════════════
         MAIN CONTENT AREA
    ════════════════════════════════════════════════════ -->
    <main style="margin-left:260px; flex:1; padding:32px; min-height:100vh; background:#000000;">

        <!-- Flash messages -->
        <?php foreach (['success', 'error', 'warning'] as $flashType): ?>
            <?php $flashMsg = get_flash($flashType); if ($flashMsg !== null): ?>
                <?php
                $flashStyles = [
                    'success' => 'background:#052e16; border:1px solid #16a34a; color:#22c55e;',
                    'error'   => 'background:#450a0a; border:1px solid #dc2626; color:#ef4444;',
                    'warning' => 'background:#451a03; border:1px solid #d97706; color:#f59e0b;',
                ];
                $flashIcons = [
                    'success' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
                    'error'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                    'warning' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                ];
                ?>
                <div id="flash-<?= $flashType ?>"
                     class="flash-message"
                     style="<?= $flashStyles[$flashType] ?> border-radius:4px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-size:14px; position:relative;">
                    <span style="flex-shrink:0;"><?= $flashIcons[$flashType] ?></span>
                    <span style="flex:1;"><?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?></span>
                    <button onclick="this.parentElement.remove()"
                            style="background:none; border:none; cursor:pointer; color:inherit; padding:0; line-height:1; font-size:18px; opacity:0.7; transition:opacity 0.15s; flex-shrink:0;"
                            onmouseover="this.style.opacity='1'"
                            onmouseout="this.style.opacity='0.7'"
                            aria-label="Dismiss">
                        &times;
                    </button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Auto-dismiss flash messages after 4 seconds -->
        <script>
            document.querySelectorAll('.flash-message').forEach(function (el) {
                setTimeout(function () {
                    el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-4px)';
                    setTimeout(function () { el.remove(); }, 400);
                }, 4000);
            });
        </script>

        <!-- Page title / breadcrumb -->
        <div style="margin-bottom:32px;">
            <h1 style="font-size:22px; font-weight:600; color:#ffffff; letter-spacing:0.02em; margin:0;">
                <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
            </h1>
        </div>

        <!-- ↓ Page content begins here ↓ -->
