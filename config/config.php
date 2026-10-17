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
    define('DB_PORT', getenv('DB_PORT') ?: '3306'); // some free hosts (e.g. Clever Cloud) use a non-standard port
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'cloudrambo_dbname');   // e.g. cpaneluser_mtrmuti
    define('DB_USER', 'cloudrambo_dbuser');
    define('DB_PASS', 'cloudrambo_dbpass');
    define('DB_PORT', '3306');
}

define('SITE_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', SITE_ROOT . '/assets/uploads');
define('UPLOAD_URL', '/assets/uploads');

if (session_status() === PHP_SESSION_NONE) {
    // Several admins work in the back office at the same time, often for a
    // long editing stretch. Keep sessions for 8 hours, and in a folder of our
    // own: on shared hosting the default session dir is shared with other
    // sites whose shorter gc_maxlifetime would delete our sessions early.
    $sessionLifetime = 8 * 60 * 60;
    $sessionDir = SITE_ROOT . '/storage/sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่าใน config/config.php');
}
