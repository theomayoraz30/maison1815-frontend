<?php
/**
 * Database connection (PDO).
 * Include this file wherever a $pdo handle is needed.
 * Sets the $pdo variable in the including scope.
 */

if (!defined('DB_HOST')) {
    require_once dirname(__DIR__, 2) . '/config.php';
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('[Maison1815] Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Database connection error. Please try again later.';
    die();
}
