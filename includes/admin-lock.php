<?php
/**
 * Second lock on the back office: after signing in with their own account,
 * an admin must also enter the shared "edit code" (รหัสแก้ไขหลังบ้าน) before
 * any admin page opens. Only the password_hash() of the code is stored
 * (site_settings.admin_edit_code_hash); the first admin to arrive sets it.
 *
 * Forgotten code: a one-time link is e-mailed to the recovery address saved
 * with the code (admin/edit-code-reset.php). Changing the code locks every
 * other admin session again.
 */

const ADMIN_LOCK_MAX_TRIES = 5;      // wrong codes before a short wait
const ADMIN_LOCK_WAIT = 60;          // seconds
const ADMIN_LOCK_RESET_TTL = 3600;   // reset link lifetime

/** Stored hash, read fresh (get_setting() caches per request, which goes stale right after a change). */
function admin_edit_code_hash() {
    global $pdo;
    $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $stmt->execute(['admin_edit_code_hash']);
    return (string) $stmt->fetchColumn();
}

function admin_edit_code_is_set() {
    return admin_edit_code_hash() !== '';
}

function admin_edit_code_check($code) {
    $hash = admin_edit_code_hash();
    return $hash !== '' && password_verify((string) $code, $hash);
}

/** Changes whenever the code changes, so old unlocks stop counting. */
function admin_edit_code_fingerprint() {
    return substr(hash('sha256', admin_edit_code_hash()), 0, 20);
}

function admin_is_unlocked() {
    return admin_edit_code_is_set()
        && !empty($_SESSION['admin_unlocked'])
        && hash_equals(admin_edit_code_fingerprint(), (string) $_SESSION['admin_unlocked']);
}

function admin_mark_unlocked() {
    session_regenerate_id(true);
    $_SESSION['admin_unlocked'] = admin_edit_code_fingerprint();
    unset($_SESSION['admin_lock_fails'], $_SESSION['admin_lock_until']);
}

/** Sends a signed-in but locked admin to the unlock screen. */
function require_admin_unlocked() {
    if (admin_is_unlocked()) {
        return;
    }
    $back = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php') : '/admin/dashboard.php';
    if (strpos($back, '/admin/') === 0) {
        $_SESSION['admin_unlock_back'] = $back;
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || basename($_SERVER['SCRIPT_NAME']) === 'presence.php') {
        http_response_code(423);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'locked']);
        exit;
    }
    header('Location: /admin/unlock.php');
    exit;
}

/** Seconds left to wait after too many wrong codes (0 = may try). */
function admin_lock_wait_left() {
    return max(0, (int) ($_SESSION['admin_lock_until'] ?? 0) - time());
}

function admin_lock_note_failure() {
    $_SESSION['admin_lock_fails'] = (int) ($_SESSION['admin_lock_fails'] ?? 0) + 1;
    if ($_SESSION['admin_lock_fails'] >= ADMIN_LOCK_MAX_TRIES) {
        $_SESSION['admin_lock_until'] = time() + ADMIN_LOCK_WAIT;
        $_SESSION['admin_lock_fails'] = 0;
    }
    sleep(1);
}

function admin_edit_code_valid_new($code, $confirm) {
    if (strlen($code) < 8) {
        return 'รหัสต้องยาวอย่างน้อย 8 ตัวอักษร';
    }
    if ($code !== $confirm) {
        return 'ยืนยันรหัสไม่ตรงกัน';
    }
    return '';
}

function admin_set_edit_code($code, $recoveryEmail = null) {
    set_setting('admin_edit_code_hash', password_hash($code, PASSWORD_DEFAULT));
    if ($recoveryEmail !== null) {
        set_setting('admin_edit_code_email', $recoveryEmail);
    }
    set_setting('admin_edit_code_reset', '');   // void any open reset link
}

/** Masked recovery e-mail for display, e.g. "sa****@gmail.com". */
function admin_edit_code_email_masked() {
    $email = (string) get_setting('admin_edit_code_email');
    if ($email === '' || strpos($email, '@') === false) {
        return '';
    }
    [$user, $domain] = explode('@', $email, 2);
    return mb_substr($user, 0, 2) . str_repeat('*', max(2, mb_strlen($user) - 2)) . '@' . $domain;
}

/** E-mails a one-time reset link to the recovery address. Returns false if none is set. */
function admin_send_edit_code_reset($requestedBy) {
    $email = (string) get_setting('admin_edit_code_email');
    if ($email === '') {
        return false;
    }
    // At most one link per 2 minutes.
    $current = json_decode((string) get_setting('admin_edit_code_reset'), true) ?: [];
    if (!empty($current['created']) && time() - (int) $current['created'] < 120) {
        return true;
    }
    $token = bin2hex(random_bytes(32));
    set_setting('admin_edit_code_reset', json_encode(['hash' => hash('sha256', $token), 'expires' => time() + ADMIN_LOCK_RESET_TTL, 'created' => time()]));
    $link = alumni_absolute_url('/admin/edit-code-reset.php?token=' . $token);
    alumni_send_mail($email, 'ตั้งรหัสแก้ไขหลังบ้านใหม่ - ' . get_site_name(),
        "มีการขอตั้งรหัสแก้ไขหลังบ้านใหม่ โดยผู้ดูแลระบบ: {$requestedBy}\n\n"
        . "กดลิงก์นี้เพื่อตั้งรหัสใหม่ (ใช้ได้ 1 ครั้ง ภายใน 1 ชั่วโมง ต้องล็อกอินหลังบ้านก่อน):\n\n{$link}\n\n"
        . "ถ้าไม่ได้ขอเอง ไม่ต้องทำอะไร รหัสเดิมยังใช้ได้ตามปกติ\n\n" . get_site_name());
    return true;
}

function admin_edit_code_reset_valid($token) {
    $current = json_decode((string) get_setting('admin_edit_code_reset'), true) ?: [];
    return preg_match('/^[a-f0-9]{64}$/', (string) $token)
        && !empty($current['hash'])
        && (int) ($current['expires'] ?? 0) > time()
        && hash_equals($current['hash'], hash('sha256', $token));
}
