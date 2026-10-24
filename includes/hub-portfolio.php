<?php
/**
 * Hub members: who they are (ศิษย์เก่า / นักศึกษาปัจจุบัน / บุคคลทั่วไป) and a
 * portfolio of free-form sections (heading, text, image, link) that is
 * shown as a printable A4 page (alumni-portfolio.php -> PDF).
 *
 * The member_type column and the portfolio table are created on first use.
 */

const ALUMNI_PORTFOLIO_MAX_ITEMS = 30;

function alumni_member_types() {
    return [
        'alumni' => 'ศิษย์เก่า',
        'student' => 'นักศึกษาปัจจุบัน',
        'general' => 'บุคคลทั่วไป',
    ];
}

function alumni_member_type_label($type) {
    return alumni_member_types()[$type] ?? alumni_member_types()['alumni'];
}

function alumni_member_type_from_post($value) {
    return array_key_exists((string) $value, alumni_member_types()) ? (string) $value : 'alumni';
}

/** "MT10 · จบปี 2565" / "MT12 · คาดว่าจบปี 2570" / "" depending on the member type. */
function alumni_class_line(array $m, $short = true) {
    $type = $m['member_type'] ?? 'alumni';
    if ($type === 'general') {
        return '';
    }
    $year = '';
    if (!empty($m['graduation_year'])) {
        $year = ($type === 'student' ? 'คาดว่าจบปี' : ($short ? 'จบปี' : 'จบปีการศึกษา')) . ' ' . $m['graduation_year'];
    }
    return trim(($m['batch_name'] ?? '') . ($year !== '' ? ' · ' . $year : ''), ' ·');
}

/** Portfolio looks the owner can pick: key => [label, description, swatch colours, QR dot colour]. */
function alumni_portfolio_themes() {
    return [
        'red' => ['label' => 'MT Red', 'desc' => 'ธีมสาขา ดำ-แดง เหมือนเว็บไซต์หลัก', 'swatch' => ['#0b0203', '#ff5a3c', '#ff9b7a'], 'qr' => '#1a0607'],
        'cyber' => ['label' => 'Cyber Neon', 'desc' => 'น้ำเงินเข้ม นีออนฟ้า-ชมพู-ม่วง', 'swatch' => ['#07080e', '#ff2d75', '#3de1ff'], 'qr' => '#0b0d16'],
        'gold' => ['label' => 'Luxury Gold', 'desc' => 'ดำ-ทอง หรูหรา', 'swatch' => ['#0c0a07', '#d6a240', '#f2c66d'], 'qr' => '#1c160c'],
        'minimal' => ['label' => 'Minimal Light', 'desc' => 'พื้นสว่าง เรียบ สะอาด ประหยัดหมึกเมื่อพิมพ์', 'swatch' => ['#f5f4f2', '#1b1b1f', '#b3261e'], 'qr' => '#1b1b1f'],
    ];
}

function alumni_portfolio_theme($value) {
    return array_key_exists((string) $value, alumni_portfolio_themes()) ? (string) $value : 'red';
}

function ensure_alumni_portfolio() {
    global $pdo;
    static $done = false;
    if ($done) {
        return;
    }
    if (get_setting('alumni_portfolio_theme_ready') !== '1') {
        try {
            $pdo->exec("ALTER TABLE alumni_members ADD COLUMN portfolio_theme VARCHAR(20) NOT NULL DEFAULT 'red'");
        } catch (PDOException $e) {
            // Column already exists, or alumni_members is created below first.
        }
    }
    if (get_setting('alumni_portfolio_ready') === '1') {
        set_setting('alumni_portfolio_theme_ready', '1');
        $done = true;
        return;
    }
    try {
        $pdo->exec("ALTER TABLE alumni_members ADD COLUMN member_type VARCHAR(20) NOT NULL DEFAULT 'alumni'");
    } catch (PDOException $e) {
        // Column already exists.
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS alumni_portfolio_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        heading VARCHAR(255) NOT NULL,
        body TEXT,
        image_path VARCHAR(255) DEFAULT NULL,
        link_url VARCHAR(500) DEFAULT NULL,
        link_label VARCHAR(150) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        INDEX (member_id),
        FOREIGN KEY (member_id) REFERENCES alumni_members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    try {
        $pdo->exec("ALTER TABLE alumni_members ADD COLUMN portfolio_theme VARCHAR(20) NOT NULL DEFAULT 'red'");
    } catch (PDOException $e) {
        // Column already exists.
    }
    set_setting('alumni_portfolio_ready', '1');
    set_setting('alumni_portfolio_theme_ready', '1');
    $done = true;
}

function get_alumni_portfolio_items($memberId) {
    global $pdo;
    ensure_alumni_portfolio();
    $stmt = $pdo->prepare('SELECT * FROM alumni_portfolio_items WHERE member_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([(int) $memberId]);
    return $stmt->fetchAll();
}

/** The member if this visitor may see their profile/portfolio, else null. */
function alumni_viewable_member($id) {
    $member = get_alumni_member((int) $id);
    if (!$member) {
        return null;
    }
    $isOwner = alumni_member_id() === (int) $member['id'];
    if ($member['status'] !== 'approved' && !$isOwner && !is_admin_logged_in()) {
        return null;
    }
    return $member;
}
