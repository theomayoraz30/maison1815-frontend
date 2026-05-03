<?php
/**
 * One-time admin user setup script.
 *
 * IMPORTANT: Delete this file immediately after first use.
 * Leaving it publicly accessible is a security risk.
 *
 * Usage: open http://localhost:8080/admin/setup.php in your browser once,
 * then remove the file from the server.
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/includes/db.php';

$message = '';
$isError = false;

// Check whether any users already exist
$stmt = $pdo->query('SELECT COUNT(*) FROM `users`');
$count = (int) $stmt->fetchColumn();

if ($count > 0) {
    $message = 'Setup already done. An admin user exists. Delete this file now.';
    $isError = true;
} else {
    // Create the admin user with a securely hashed password
    $username = 'admin';
    $password = 'admin1815';
    $hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $insert = $pdo->prepare('INSERT INTO `users` (`username`, `password`) VALUES (:username, :password)');
    $insert->execute([':username' => $username, ':password' => $hash]);

    $message = 'Admin user created successfully. <strong>Delete this file now!</strong>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maison 1815 — Setup</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0a0a0a;
            color: #e5e5e5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card {
            background: #111;
            border: 1px solid #222;
            border-radius: 8px;
            padding: 2.5rem 3rem;
            max-width: 480px;
            width: 100%;
        }

        h1 {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            color: #fff;
        }

        .message {
            padding: 1rem 1.25rem;
            border-radius: 4px;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .message.success {
            background: rgba(255, 85, 0, 0.1);
            border: 1px solid rgba(255, 85, 0, 0.4);
            color: #ff9966;
        }

        .message.error {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
        }

        .credentials {
            background: #0a0a0a;
            border: 1px solid #1e1e1e;
            border-radius: 4px;
            padding: 1rem 1.25rem;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .credentials p { margin-bottom: 0.4rem; color: #999; }
        .credentials p:last-child { margin-bottom: 0; }
        .credentials strong { color: #e5e5e5; }

        a.btn {
            display: inline-block;
            padding: 0.65rem 1.5rem;
            background: #FF5500;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: background 0.2s;
        }

        a.btn:hover { background: #e04a00; }

        .warning {
            margin-top: 1.5rem;
            font-size: 0.78rem;
            color: #dc2626;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Maison 1815 — Setup</h1>

        <div class="message <?= $isError ? 'error' : 'success' ?>">
            <?= $message ?>
        </div>

        <?php if (!$isError): ?>
        <div class="credentials">
            <p>Username: <strong>admin</strong></p>
            <p>Password: <strong>admin1815</strong></p>
        </div>

        <a href="login.php" class="btn">Go to login</a>
        <?php endif; ?>

        <p class="warning">&#9888; Delete this file from the server immediately.</p>
    </div>
</body>
</html>
