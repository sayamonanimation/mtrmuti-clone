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
