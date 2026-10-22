<?php
/**
 * "ติดต่องานศิษย์เก่า" - alumni self-service portfolio hub.
 *
 * Alumni register an account (email + password), fill in their profile and
 * upload works. A profile appears on the public page only after an admin
 * approves it (admin/alumni-hub.php). Approved members can keep editing;
 * admins can hide a profile at any time.
 *
 * Tables, page row and a menu item under "ศิษย์เก่า" are created on first use.
 */

const ALUMNI_HUB_MAX_WORKS = 12;

function ensure_alumni_hub() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('alumni_hub_ready') === '1') {
        $done = true;
        ensure_alumni_portfolio();
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS alumni_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        batch_name VARCHAR(100) DEFAULT NULL,
        graduation_year INT DEFAULT NULL,
        photo_path VARCHAR(255) DEFAULT NULL,
        headline VARCHAR(255) DEFAULT NULL,
        skills VARCHAR(500) DEFAULT NULL,
        bio TEXT,
        contact_email VARCHAR(190) DEFAULT NULL,
        contact_phone VARCHAR(50) DEFAULT NULL,
        line_id VARCHAR(100) DEFAULT NULL,
        website_url VARCHAR(255) DEFAULT NULL,
        available_for_work TINYINT(1) NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        admin_note VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS alumni_works (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_path VARCHAR(255) DEFAULT NULL,
        link_url VARCHAR(255) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (member_id),
        FOREIGN KEY (member_id) REFERENCES alumni_members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $exists = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
    $exists->execute(['alumni-hub']);
    if (!$exists->fetchColumn()) {
        $pdo->prepare('INSERT INTO pages (slug, title, title_en, subtitle, content) VALUES (?, ?, ?, ?, ?)')
            ->execute(['alumni-hub', 'ติดต่องานศิษย์เก่า', 'Hire Our Alumni', 'รวมผลงานและช่องทางติดต่อศิษย์เก่าสาขาเทคโนโลยีมัลติมีเดีย', '']);
    }
    $hasItem = $pdo->query("SELECT COUNT(*) FROM nav_menu_items WHERE url IN ('/alumni-hub.php', '/alumni-hub')")->fetchColumn();
    if (!$hasItem) {
        $parent = $pdo->query("SELECT id FROM nav_menu_items WHERE url IN ('/alumni.php', '/alumni') ORDER BY id LIMIT 1")->fetchColumn();
        if ($parent) {
            $pdo->prepare('INSERT INTO nav_menu_items (parent_id, label, label_en, url, sort_order, is_active) VALUES (?, ?, ?, ?, 1, 1)')
                ->execute([$parent, 'ติดต่องานศิษย์เก่า', 'Hire Our Alumni', '/alumni-hub.php']);
        }
    }
    set_setting('alumni_hub_ready', '1');
    $done = true;
    ensure_alumni_portfolio();
}

// ---------- Consent to publish personal data (PDPA) ----------

/** Bump when the consent text on alumni-register.php changes. */
const ALUMNI_CONSENT_VERSION = '1';

/** alumni_members.consent_at / consent_version: when and to which text the member agreed. */
function ensure_alumni_consent_columns() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('alumni_consent_ready') === '1') {
        $done = true;
        return;
    }
    foreach (['consent_at DATETIME DEFAULT NULL', 'consent_version VARCHAR(20) DEFAULT NULL'] as $column) {
        try {
            $pdo->exec('ALTER TABLE alumni_members ADD COLUMN ' . $column);
        } catch (PDOException $e) {
            // Column already exists.
        }
    }
    set_setting('alumni_consent_ready', '1');
    $done = true;
}

// ---------- Member session (separate from the admin session) ----------

function alumni_member_id() {
    return (int) ($_SESSION['alumni_member_id'] ?? 0);
}

function current_alumni_member() {
    global $pdo;
    $id = alumni_member_id();
    if (!$id) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM alumni_members WHERE id = ?');
    $stmt->execute([$id]);
    $member = $stmt->fetch();
    if (!$member) {
        unset($_SESSION['alumni_member_id']);
    }
    return $member ?: null;
}

function require_alumni_login() {
    if (!current_alumni_member()) {
        header('Location: ' . clean_public_url('/alumni-login'));
        exit;
    }
}

// ---------- Queries ----------

function get_alumni_member($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM alumni_members WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch() ?: null;
}

/** Approved members with their first work image and work count. */
function get_approved_alumni_members() {
    global $pdo;
    ensure_alumni_hub();
    return $pdo->query("SELECT m.*, COUNT(w.id) AS work_count,
            (SELECT w2.image_path FROM alumni_works w2 WHERE w2.member_id = m.id AND w2.image_path IS NOT NULL ORDER BY w2.sort_order, w2.id LIMIT 1) AS cover_work
        FROM alumni_members m LEFT JOIN alumni_works w ON w.member_id = m.id
        WHERE m.status = 'approved'
        GROUP BY m.id
        ORDER BY m.available_for_work DESC, COALESCE(m.updated_at, m.approved_at) DESC, m.id DESC")->fetchAll();
}

function get_alumni_works($memberId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM alumni_works WHERE member_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([(int) $memberId]);
    return $stmt->fetchAll();
}

function alumni_skill_list($skills) {
    return array_slice(array_values(array_filter(array_map('trim', preg_split('/[,،\n]+/u', (string) $skills)))), 0, 12);
}

function alumni_status_label($status) {
    return ['pending' => 'รอตรวจสอบ', 'approved' => 'เผยแพร่แล้ว', 'rejected' => 'ไม่ผ่าน', 'hidden' => 'ซ่อนอยู่'][$status] ?? $status;
}

/** Small honeypot + timing check against bots on the public forms. */
function alumni_form_is_bot() {
    if (!empty($_POST['website_confirm'])) {   // hidden field humans never fill
        return true;
    }
    $started = (int) ($_POST['form_started'] ?? 0);
    return $started > 0 && time() - $started < 3;
}

// ---------- Self-service password reset ----------
// A random token is e-mailed as a link; only its SHA-256 hash is stored, it
// expires after an hour and works once. Passwords live only in the database
// as password_hash() output.

const ALUMNI_RESET_TTL = 3600;

function ensure_alumni_password_resets() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('alumni_password_resets_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS alumni_password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL,
        INDEX (token_hash),
        INDEX (member_id),
        FOREIGN KEY (member_id) REFERENCES alumni_members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    set_setting('alumni_password_resets_ready', '1');
    $done = true;
}

function alumni_absolute_url($path) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^a-z0-9.:-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https://' : 'http://') . $host . clean_public_url($path);
}

function alumni_send_mail($to, $subject, $body) {
    $host = preg_replace('/^www\.|:\d+$/', '', preg_replace('/[^a-z0-9.:-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = implode("\r\n", [
        'From: =?UTF-8?B?' . base64_encode(get_site_name()) . '?= <no-reply@' . $host . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ]);
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', chunk_split(base64_encode($body)), $headers);
}

/** Creates a reset link for the e-mail if it belongs to a member. Silent otherwise. */
function alumni_request_password_reset($email) {
    global $pdo;
    ensure_alumni_password_resets();
    $stmt = $pdo->prepare('SELECT id, email, full_name FROM alumni_members WHERE email = ?');
    $stmt->execute([mb_strtolower(trim($email))]);
    $member = $stmt->fetch();
    if (!$member) {
        return;
    }
    // At most 3 links per member per 15 minutes.
    $recent = $pdo->prepare('SELECT COUNT(*) FROM alumni_password_resets WHERE member_id = ? AND created_at > ?');
    $recent->execute([$member['id'], date('Y-m-d H:i:s', time() - 900)]);
    if ((int) $recent->fetchColumn() >= 3) {
        return;
    }
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO alumni_password_resets (member_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$member['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + ALUMNI_RESET_TTL), date('Y-m-d H:i:s')]);
    $link = alumni_absolute_url('/alumni-reset?token=' . $token);
    alumni_send_mail($member['email'], 'ตั้งรหัสผ่านใหม่ - ' . get_site_name(),
        "สวัสดีคุณ {$member['full_name']}\n\n"
        . "มีการขอตั้งรหัสผ่านใหม่สำหรับบัญชีติดต่องานศิษย์เก่า กดลิงก์นี้เพื่อตั้งรหัสผ่านใหม่ (ใช้ได้ 1 ครั้ง ภายใน 1 ชั่วโมง):\n\n"
        . "$link\n\n"
        . "ถ้าคุณไม่ได้เป็นผู้ขอ ไม่ต้องทำอะไร รหัสผ่านเดิมยังใช้ได้ตามปกติ\n\n" . get_site_name());
}

/** Member row for a valid, unused, unexpired token, or null. */
function alumni_find_reset($token) {
    global $pdo;
    ensure_alumni_password_resets();
    if (!preg_match('/^[a-f0-9]{64}$/', (string) $token)) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT r.id AS reset_id, m.id, m.email, m.full_name FROM alumni_password_resets r
        JOIN alumni_members m ON m.id = r.member_id
        WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?');
    $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    return $stmt->fetch() ?: null;
}

/** Saves a new password hash, voids every open reset link and tells the member by e-mail. */
function alumni_set_password($member, $newPassword) {
    global $pdo;
    ensure_alumni_password_resets();
    $pdo->prepare('UPDATE alumni_members SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $member['id']]);
    $pdo->prepare('UPDATE alumni_password_resets SET used_at = ? WHERE member_id = ? AND used_at IS NULL')
        ->execute([date('Y-m-d H:i:s'), $member['id']]);
    alumni_send_mail($member['email'], 'รหัสผ่านถูกเปลี่ยนแล้ว - ' . get_site_name(),
        "สวัสดีคุณ {$member['full_name']}\n\n"
        . 'รหัสผ่านบัญชีติดต่องานศิษย์เก่าของคุณถูกเปลี่ยนเมื่อ ' . date('d/m/Y H:i') . " น.\n\n"
        . "ถ้าคุณไม่ได้เป็นผู้เปลี่ยน กรุณาตั้งรหัสผ่านใหม่ทันทีที่ " . alumni_absolute_url('/alumni-forgot') . " และแจ้งเจ้าหน้าที่สาขา\n\n" . get_site_name());
}

require_once __DIR__ . '/hub-portfolio.php';
