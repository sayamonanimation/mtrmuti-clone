<?php
/**
 * Central config: DB connection + session bootstrap.
 * Fill in your CloudRambo cPanel MySQL credentials below
 * (cPanel -> MySQL Databases). Then update DB_* constants.
 *
 * For local development, create config/config.local.php (gitignored) defining
 * the same DB_* constants instead of editing this file - it's loaded first
 * and takes precedence, so this file's CloudRambo placeholders stay deploy-ready.
 */

if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// Cloud demo hosts (Render, Railway, Docker, ...) inject DB credentials as
// environment variables instead of a config.local.php file.
if (!defined('DB_HOST') && getenv('DB_HOST') !== false) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'cloudrambo_dbname');   // e.g. cpaneluser_mtrmuti
    define('DB_USER', 'cloudrambo_dbuser');
    define('DB_PASS', 'cloudrambo_dbpass');
}

define('SITE_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', SITE_ROOT . '/assets/uploads');
define('UPLOAD_URL', '/assets/uploads');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่าใน config/config.php');
}
