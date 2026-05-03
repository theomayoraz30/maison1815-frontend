<?php
/**
 * login.php — Admin authentication page.
 *
 * Handles brute-force protection (session-based counters), CSRF verification,
 * password verification, and session fixation prevention on successful login.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Start session with hardened cookie params
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Already logged in — bounce to dashboard
if (isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0) {
    redirect('/admin/index.php');
}

// ── Brute-force state ─────────────────────────────────────────────────────────
const MAX_ATTEMPTS    = 5;
const LOCKOUT_SECONDS = 900; // 15 minutes

$attempts      = (int) ($_SESSION['login_attempts']      ?? 0);
$lockoutUntil  = (int) ($_SESSION['login_lockout_until'] ?? 0);
$now           = time();
$isLockedOut   = $lockoutUntil > $now;
$remainingTime = $isLockedOut ? ($lockoutUntil - $now) : 0;

$errorMessage   = '';
$lockoutMessage = '';

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Reject login attempts while locked out
    if ($isLockedOut) {
        $minutes = (int) ceil($remainingTime / 60);
        $lockoutMessage = sprintf(
            'Too many failed attempts. Please try again in %d minute%s.',
            $minutes,
            $minutes !== 1 ? 's' : ''
        );
    } else {
        // CSRF check (helpers.php provides csrf_verify())
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $errorMessage = 'Invalid request. Please refresh the page and try again.';
        } else {
            $username = trim((string) ($_POST['username'] ?? ''));
            // Do NOT htmlspecialchars passwords — they may contain special chars intentionally
            $password = (string) ($_POST['password'] ?? '');

            if ($username === '' || $password === '') {
                $errorMessage = 'Please enter your username and password.';
            } else {
                // Look up user by username using a prepared statement
                $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user !== false && password_verify($password, (string) $user['password'])) {
                    // ── Successful login ──────────────────────────────────────
                    session_regenerate_id(true); // prevent session fixation

                    $_SESSION['admin_id']          = (int) $user['id'];
                    $_SESSION['admin_username']    = (string) $user['username'];
                    $_SESSION['last_regenerated']  = $now;

                    // Clear brute-force counters
                    unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);

                    // Honour a safe "next" redirect if present
                    $next = filter_input(INPUT_GET, 'next', FILTER_SANITIZE_URL) ?? '';
                    $dest = (str_starts_with($next, '/admin/') && !str_contains($next, '..'))
                        ? $next
                        : '/admin/index.php';

                    redirect($dest);
                } else {
                    // ── Failed login ──────────────────────────────────────────
                    $attempts++;
                    $_SESSION['login_attempts'] = $attempts;

                    if ($attempts >= MAX_ATTEMPTS) {
                        $_SESSION['login_lockout_until'] = $now + LOCKOUT_SECONDS;
                        $lockoutMessage = 'Too many failed attempts. Account locked for 15 minutes.';
                        $isLockedOut    = true;
                    } else {
                        $remaining    = MAX_ATTEMPTS - $attempts;
                        $errorMessage = sprintf(
                            'Invalid credentials. %d attempt%s remaining before lockout.',
                            $remaining,
                            $remaining !== 1 ? 's' : ''
                        );
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Maison 1815</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            DEFAULT: '#FF5500',
                            hover:   '#e64d00',
                        },
                    },
                    fontFamily: {
                        display: ['"SF Pro Display"', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <style>
        @font-face {
            font-family: 'SF Pro Display';
            src: url('/sf-pro-display/SF-Pro-Display-Regular.otf') format('opentype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'SF Pro Display';
            src: url('/sf-pro-display/SF-Pro-Display-Medium.otf') format('opentype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'SF Pro Display';
            src: url('/sf-pro-display/SF-Pro-Display-Bold.otf') format('opentype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        /* Remove browser autofill yellow background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #111111 inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: #ffffff;
        }
    </style>
</head>
<body class="bg-black min-h-screen flex items-center justify-center font-display px-4">

    <div class="w-full max-w-sm">

        <!-- Brand -->
        <div class="text-center mb-10">
            <h1 class="text-white text-2xl font-bold tracking-[0.25em] uppercase">
                Maison 1815
            </h1>
            <p class="text-[#888888] text-sm mt-2 tracking-widest uppercase">
                Admin Dashboard
            </p>
        </div>

        <!-- Card -->
        <div class="bg-[#0a0a0a] border border-[#1a1a1a] rounded-sm p-8">

            <?php if ($lockoutMessage !== ''): ?>
                <!-- Lockout notice -->
                <div class="mb-6 px-4 py-3 bg-amber-950/40 border border-amber-700/50 rounded-sm text-amber-400 text-sm leading-snug">
                    <?= htmlspecialchars($lockoutMessage) ?>
                </div>
            <?php elseif ($errorMessage !== ''): ?>
                <!-- Error notice -->
                <div class="mb-6 px-4 py-3 bg-red-950/40 border border-red-700/50 rounded-sm text-red-400 text-sm leading-snug">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/login.php<?= isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '' ?>" novalidate>

                <!-- CSRF token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                <!-- Username -->
                <div class="mb-5">
                    <label for="username" class="block text-[#888888] text-xs tracking-widest uppercase mb-2">
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        required
                        <?= $isLockedOut ? 'disabled' : '' ?>
                        value="<?= htmlspecialchars((string) ($_POST['username'] ?? '')) ?>"
                        class="w-full bg-[#111111] border border-[#2a2a2a] text-white text-sm rounded-sm px-4 py-3
                               focus:outline-none focus:border-[#FF5500] transition-colors duration-150
                               placeholder-[#444444] disabled:opacity-40 disabled:cursor-not-allowed"
                        placeholder="Enter username"
                    >
                </div>

                <!-- Password -->
                <div class="mb-7">
                    <label for="password" class="block text-[#888888] text-xs tracking-widest uppercase mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        <?= $isLockedOut ? 'disabled' : '' ?>
                        class="w-full bg-[#111111] border border-[#2a2a2a] text-white text-sm rounded-sm px-4 py-3
                               focus:outline-none focus:border-[#FF5500] transition-colors duration-150
                               placeholder-[#444444] disabled:opacity-40 disabled:cursor-not-allowed"
                        placeholder="Enter password"
                    >
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    <?= $isLockedOut ? 'disabled' : '' ?>
                    class="w-full bg-[#FF5500] hover:bg-[#e64d00] active:bg-[#cc4400]
                           text-white text-sm font-medium tracking-widest uppercase
                           px-4 py-3 rounded-sm transition-colors duration-150
                           disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    Sign In
                </button>

            </form>
        </div>

        <!-- Footer note -->
        <p class="text-center text-[#333333] text-xs mt-8 tracking-widest uppercase">
            &copy; <?= date('Y') ?> Maison 1815
        </p>

    </div>

</body>
</html>
