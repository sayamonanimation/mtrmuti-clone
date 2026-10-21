<?php
/** Shared helper functions used by both the public site and the admin panel. */

// Language selection: ?lang=en|th sets a year-long cookie; falls back to Thai.
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'], true)) {
    setcookie('lang', $_GET['lang'], time() + 60 * 60 * 24 * 365, '/');
    $_COOKIE['lang'] = $_GET['lang'];
}
$GLOBALS['current_lang'] = in_array($_COOKIE['lang'] ?? '', ['th', 'en'], true) ? $_COOKIE['lang'] : 'th';

function current_lang() {
    return $GLOBALS['current_lang'] ?? 'th';
}

/** Static UI string lookup, e.g. t('nav.home'). Falls back to the key itself if missing. */
function t($key) {
    static $dict = null;
    if ($dict === null) {
        $dict = require __DIR__ . '/i18n-strings.php';
    }
    $entry = $dict[$key] ?? null;
    if ($entry === null) {
        return $key;
    }
    return $entry[current_lang()] ?? $entry['th'] ?? $key;
}

/**
 * Reads a bilingual DB field: pass the row and the Thai column name, e.g.
 * lang_field($page, 'title') reads 'title' or 'title_en' depending on the
 * current language. Falls back to the Thai value if no English translation
 * has been filled in yet, so untranslated content never renders blank.
 */
function lang_field($row, $field) {
    if (current_lang() === 'en' && !empty($row[$field . '_en'])) {
        return $row[$field . '_en'];
    }
    return $row[$field] ?? '';
}

function get_site_name() {
    $default = 'สาขาวิชาเทคโนโลยีมัลติมีเดีย';
    if (current_lang() === 'en') {
        $en = get_setting('site_name_en');
        if ($en) {
            return $en;
        }
    }
    return get_setting('site_name', $default);
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Adds an https:// scheme to a URL an admin typed without one (e.g.
 * "www.rmuti.ac.th" instead of "https://www.rmuti.ac.th"). Applied
 * server-side so form fields can stay <input type="text"> - a native
 * type="url" input silently blocks submission for exactly this kind of
 * input, which reads as "the save button doesn't work".
 */
function normalize_url($url) {
    $url = trim((string) $url);
    if ($url === '' || preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
        return $url;
    }
    return 'https://' . $url;
}

/**
 * Like normalize_url(), but for nav menu URLs which can also legitimately be
 * an internal absolute path (e.g. "/teachers.php") or "#" for a dropdown-only
 * parent - those must NOT get "https://" prepended.
 */
function normalize_nav_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    if ($url === '#' || strpos($url, '/') === 0) {
        return $url;
    }
    return normalize_url($url);
}

/** Extracts the 11-char video ID from a youtube.com/watch, youtu.be, or /embed/ URL. */
function youtube_video_id($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
        return $m[1];
    }
    return null;
}

function thai_date($dateStr) {
    if (!$dateStr) {
        return '';
    }
    $ts = strtotime($dateStr);
    if ($ts === false) {
        return '';
    }
    if (current_lang() === 'en') {
        static $monthsEn = [1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        return $monthsEn[(int) date('n', $ts)] . ' ' . (int) date('j', $ts) . ', ' . (int) date('Y', $ts);
    }
    static $months = [1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . ((int) date('Y', $ts) + 543);
}

function ini_size_to_bytes($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $number = (int) $value;
    switch ($unit) {
        case 'g': return $number * 1024 * 1024 * 1024;
        case 'm': return $number * 1024 * 1024;
        case 'k': return $number * 1024;
        default: return (int) $value;
    }
}

/**
 * When an upload exceeds post_max_size, PHP empties $_POST/$_FILES before the
 * script even runs (only a low-level warning is logged), so csrf_check() and
 * every other check then fails with confusing, unrelated errors. Call this
 * first on any page that accepts file uploads to show a clear message instead.
 */
function reject_oversized_upload($redirectTo) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    $limit = ini_size_to_bytes(ini_get('post_max_size'));
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($limit > 0 && $contentLength > $limit) {
        $limitMb = round($limit / (1024 * 1024), 1);
        set_flash("ไฟล์ที่อัพโหลดมีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์อนุญาต (จำกัดไว้ที่ {$limitMb} MB) กรุณาเลือกไฟล์ที่มีขนาดเล็กลง หรือแจ้งผู้ดูแลระบบให้เพิ่มค่า upload_max_filesize/post_max_size", 'error');
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * Renders free-text admin input as a bullet list when it has more than one
 * line (one entry per line), or a single paragraph when it's just one line.
 * Used for teacher profile sections (research works, awards, etc.) that can
 * hold either a single note or several distinct items.
 */
function render_text_list($text) {
    $lines = preg_split('/\r\n|\r|\n/', trim((string) $text));
    $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));

    if (empty($lines)) {
        return '';
    }
    if (count($lines) === 1) {
        return '<p>' . h($lines[0]) . '</p>';
    }

    $html = '<ul class="profile-list">';
    foreach ($lines as $line) {
        $html .= '<li>' . h($line) . '</li>';
    }
    return $html . '</ul>';
}

function get_page($slug) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
    return $page ?: ['slug' => $slug, 'title' => '', 'subtitle' => '', 'content' => ''];
}

function get_setting($key, $default = '') {
    global $pdo;
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function get_active_sliders() {
    global $pdo;
    return $pdo->query('SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();
}

function get_active_posts($limit = null) {
    global $pdo;
    $sql = 'SELECT * FROM posts WHERE is_active = 1 ORDER BY sort_order ASC, post_date DESC, id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    return $pdo->query($sql)->fetchAll();
}

function get_all_posts() {
    global $pdo;
    return $pdo->query('SELECT * FROM posts ORDER BY sort_order ASC, post_date DESC, id DESC')->fetchAll();
}

function get_post($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * The statistics page (statistics.php) and its menu entry under
 * "ฐานข้อมูลนักศึกษา" are created on first use, so the live site needs no
 * manual SQL. Guarded by a setting so it runs once; admins can rename or
 * move the menu item afterwards in "จัดการเมนูเว็บไซต์".
 */
function ensure_statistics_page() {
    global $pdo;
    if (get_setting('statistics_page_ready') === '1') {
        return;
    }
    $exists = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
    $exists->execute(['statistics']);
    if (!$exists->fetchColumn()) {
        $pdo->prepare('INSERT INTO pages (slug, title, title_en, subtitle, content) VALUES (?, ?, ?, ?, ?)')
            ->execute(['statistics', 'สถิติการรับนักศึกษา', 'Admission Statistics', '', '']);
    }
    $hasItem = $pdo->query("SELECT COUNT(*) FROM nav_menu_items WHERE url IN ('/statistics.php', '/statistics')")->fetchColumn();
    if (!$hasItem) {
        $parent = $pdo->query("SELECT id FROM nav_menu_items WHERE parent_id IS NULL AND label = 'ฐานข้อมูลนักศึกษา' LIMIT 1")->fetchColumn();
        if ($parent) {
            $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM nav_menu_items WHERE parent_id = ' . (int) $parent)->fetchColumn();
            $pdo->prepare('INSERT INTO nav_menu_items (parent_id, label, label_en, url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)')
                ->execute([$parent, 'สถิติการรับนักศึกษา', 'Admission Statistics', '/statistics.php', $sort]);
        }
    }
    set_setting('statistics_page_ready', '1');
}

/**
 * Outstanding alumni (outstanding_alumni table, outstanding-alumni.php):
 * table, page row and a menu item nested under "ศิษย์เก่า" are created on
 * first use, once (setting guard).
 */
function ensure_outstanding_alumni() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('outstanding_alumni_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS outstanding_alumni (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        photo_path VARCHAR(255) DEFAULT NULL,
        batch_name VARCHAR(100) DEFAULT NULL,
        graduation_year INT DEFAULT NULL,
        position VARCHAR(255) DEFAULT NULL,
        workplace VARCHAR(255) DEFAULT NULL,
        achievement TEXT,
        quote TEXT,
        link_url VARCHAR(255) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $exists = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
    $exists->execute(['outstanding-alumni']);
    if (!$exists->fetchColumn()) {
        $pdo->prepare('INSERT INTO pages (slug, title, title_en, subtitle, content) VALUES (?, ?, ?, ?, ?)')
            ->execute(['outstanding-alumni', 'ศิษย์เก่าดีเด่น', 'Outstanding Alumni', '', '']);
    }
    $hasItem = $pdo->query("SELECT COUNT(*) FROM nav_menu_items WHERE url IN ('/outstanding-alumni.php', '/outstanding-alumni')")->fetchColumn();
    if (!$hasItem) {
        $parent = $pdo->query("SELECT id FROM nav_menu_items WHERE url IN ('/alumni.php', '/alumni') ORDER BY id LIMIT 1")->fetchColumn();
        if ($parent) {
            $pdo->prepare('INSERT INTO nav_menu_items (parent_id, label, label_en, url, sort_order, is_active) VALUES (?, ?, ?, ?, 0, 1)')
                ->execute([$parent, 'ศิษย์เก่าดีเด่น', 'Outstanding Alumni', '/outstanding-alumni.php']);
        }
    }
    set_setting('outstanding_alumni_ready', '1');
    $done = true;
}

function get_outstanding_alumni($activeOnly = true) {
    global $pdo;
    ensure_outstanding_alumni();
    $sql = 'SELECT * FROM outstanding_alumni' . ($activeOnly ? ' WHERE is_active = 1' : '')
         . ' ORDER BY sort_order ASC, graduation_year IS NULL ASC, graduation_year DESC, id DESC';
    return $pdo->query($sql)->fetchAll();
}

function get_stats() {
    global $pdo;
    return $pdo->query('SELECT * FROM stats ORDER BY sort_order ASC, year ASC')->fetchAll();
}

function get_fields_of_study() {
    global $pdo;
    return $pdo->query('SELECT * FROM fields_of_study ORDER BY sort_order ASC')->fetchAll();
}

/**
 * Teacher "identity" (free-text expertise keywords typed in the admin, e.g.
 * "เขียนโปรแกรม, อัลกอริทึม, พัฒนาเกม") drives the themed effects on the
 * teacher photo. The column is added on first use, so no manual migration.
 */
function ensure_teacher_identity_column() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('teacher_identity_ready') === '1') {
        $done = true;
        return;
    }
    try {
        $pdo->exec('ALTER TABLE teachers ADD COLUMN identity_tags VARCHAR(255) DEFAULT NULL');
    } catch (PDOException $e) {
        // Column already exists.
    }
    set_setting('teacher_identity_ready', '1');
    $done = true;
}

/** show_phone (1 = show the teacher's phone on the public site, default hidden) - added on first use. */
function ensure_teacher_show_phone_column() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('teacher_show_phone_ready') === '1') {
        $done = true;
        return;
    }
    try {
        // Hidden by default (requested: all teacher phones hidden); admins
        // switch individual numbers back on in the teacher form.
        $pdo->exec('ALTER TABLE teachers ADD COLUMN show_phone TINYINT(1) NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE teachers SET show_phone = 0');
    } catch (PDOException $e) {
        // Column already exists.
    }
    set_setting('teacher_show_phone_ready', '1');
    $done = true;
}

/** Phone number to print publicly, or '' when the teacher chose to hide it. */
function teacher_public_phone($teacher) {
    if (isset($teacher['show_phone']) && (int) $teacher['show_phone'] === 0) {
        return '';
    }
    return (string) ($teacher['phone'] ?? '');
}

/** theme => keywords (Thai + English, matched case-insensitively inside each tag). */
const TEACHER_IDENTITY_THEMES = [
    'code' => ['โปรแกรม', 'โค้ด', 'โค๊ด', 'program', 'coding', 'code', 'developer', 'software', 'ซอฟต์แวร์', 'python', 'java', 'php', 'c++', 'javascript'],
    'algorithm' => ['อัลกอริทึม', 'อัลกอริธึม', 'algorithm', 'โครงสร้างข้อมูล', 'data structure', 'ai', 'ปัญญาประดิษฐ์', 'machine learning', 'คณิต', 'math', 'ข้อมูล', 'data'],
    'game' => ['เกม', 'game', 'unity', 'unreal', 'esport', 'gamification'],
    'web' => ['เว็บ', 'web', 'html', 'css', 'ui', 'ux', 'แอป', 'app', 'mobile'],
    'animation' => ['แอนิเมชัน', 'แอนิเมชั่น', 'อนิเมชั่น', 'animation', 'animate', 'motion', 'การ์ตูน', 'cartoon', '2d', 'character'],
    'threed' => ['3d', '3 มิติ', 'สามมิติ', 'modeling', 'โมเดล', 'blender', 'maya', 'vr', 'ar', 'metaverse', 'xr'],
    'film' => ['ภาพยนตร์', 'หนัง', 'film', 'video', 'วิดีโอ', 'วีดีโอ', 'ตัดต่อ', 'edit', 'cinema', 'กำกับ', 'director', 'โฆษณา', 'tvc', 'broadcast'],
    'photo' => ['ถ่ายภาพ', 'ภาพถ่าย', 'photo', 'กล้อง', 'camera', 'lighting', 'แสง'],
    'design' => ['ออกแบบ', 'design', 'กราฟิก', 'graphic', 'ศิลป', 'art', 'วาด', 'drawing', 'illustrat', 'typography', 'สี', 'branding', 'แบรนด์'],
    'sound' => ['เสียง', 'sound', 'audio', 'ดนตรี', 'music', 'เพลง', 'podcast'],
];

/** ['tags' => [...], 'themes' => [...]] for a teacher row. */
function teacher_identity($teacher) {
    $raw = (string) ($teacher['identity_tags'] ?? '');
    $tags = array_slice(array_values(array_filter(array_map('trim', preg_split('/[,،\n]+/u', $raw)))), 0, 6);
    $themes = [];
    foreach ($tags as $tag) {
        $lower = mb_strtolower($tag, 'UTF-8');
        foreach (TEACHER_IDENTITY_THEMES as $theme => $keywords) {
            foreach ($keywords as $kw) {
                // Short latin keywords (ai, ar, ui...) must match a whole word.
                $hit = (strlen($kw) <= 3 && preg_match('/^[a-z0-9+]+$/', $kw))
                    ? preg_match('/(^|[^a-z])' . preg_quote($kw, '/') . '([^a-z]|$)/', $lower)
                    : mb_strpos($lower, $kw) !== false;
                if ($hit) {
                    $themes[$theme] = true;
                    break;
                }
            }
        }
    }
    return ['tags' => $tags, 'themes' => array_keys($themes)];
}

function get_teachers() {
    global $pdo;
    ensure_teacher_identity_column();
    ensure_teacher_show_phone_column();
    return $pdo->query('SELECT * FROM teachers ORDER BY sort_order ASC, name ASC')->fetchAll();
}

function get_teacher($id) {
    global $pdo;
    ensure_teacher_identity_column();
    ensure_teacher_show_phone_column();
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/** Work categories that support attaching photo evidence via teacher_portfolio. */
function portfolio_categories() {
    return [
        'research_works' => t('profile.research_works'),
        'creative_works' => t('profile.creative_works'),
        'innovation_works' => t('profile.innovation_works'),
        'competition_works' => t('profile.competition_works'),
        'intellectual_property' => t('profile.intellectual_property'),
    ];
}

function get_teacher_portfolio($teacherId, $category = null) {
    global $pdo;
    if ($category !== null) {
        $stmt = $pdo->prepare('SELECT * FROM teacher_portfolio WHERE teacher_id = ? AND category = ? ORDER BY sort_order ASC, created_at DESC');
        $stmt->execute([$teacherId, $category]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM teacher_portfolio WHERE teacher_id = ? ORDER BY category ASC, sort_order ASC, created_at DESC');
        $stmt->execute([$teacherId]);
    }
    return $stmt->fetchAll();
}

const GALLERY_ALBUM_MAX_PHOTOS = 80;

/**
 * Activity photos are grouped into albums (gallery_albums + gallery.album_id).
 * Created on first use; photos uploaded before albums existed go into one
 * "ภาพกิจกรรมทั่วไป" album so nothing disappears from the site.
 */
function ensure_gallery_albums() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('gallery_albums_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS gallery_albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        title_en VARCHAR(255) DEFAULT NULL,
        description TEXT,
        event_date DATE DEFAULT NULL,
        cover_path VARCHAR(255) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    try {
        $pdo->exec('ALTER TABLE gallery ADD COLUMN album_id INT DEFAULT NULL');
    } catch (PDOException $e) {
        // Column already exists.
    }
    $orphans = (int) $pdo->query('SELECT COUNT(*) FROM gallery WHERE album_id IS NULL')->fetchColumn();
    if ($orphans > 0) {
        $pdo->prepare('INSERT INTO gallery_albums (title, title_en) VALUES (?, ?)')->execute(['ภาพกิจกรรมทั่วไป', 'General activities']);
        $albumId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE gallery SET album_id = ? WHERE album_id IS NULL')->execute([$albumId]);
    }
    set_setting('gallery_albums_ready', '1');
    $done = true;
}

/** Albums with photo_count and a cover (chosen cover, else the first photo). */
function get_gallery_albums($limit = null) {
    global $pdo;
    ensure_gallery_albums();
    $sql = 'SELECT a.*, COUNT(g.id) AS photo_count,
                   (SELECT g2.image_path FROM gallery g2 WHERE g2.album_id = a.id ORDER BY g2.sort_order ASC, g2.id ASC LIMIT 1) AS first_photo
            FROM gallery_albums a LEFT JOIN gallery g ON g.album_id = a.id
            GROUP BY a.id
            ORDER BY a.sort_order ASC, COALESCE(a.event_date, a.created_at) DESC, a.id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $albums = $pdo->query($sql)->fetchAll();
    foreach ($albums as &$album) {
        $album['cover'] = $album['cover_path'] ?: $album['first_photo'];
    }
    return $albums;
}

function get_gallery_album($id) {
    global $pdo;
    ensure_gallery_albums();
    $stmt = $pdo->prepare('SELECT * FROM gallery_albums WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch() ?: null;
}

function get_album_photos($albumId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM gallery WHERE album_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([(int) $albumId]);
    return $stmt->fetchAll();
}

function get_gallery($limit = null) {
    global $pdo;
    $sql = 'SELECT * FROM gallery ORDER BY sort_order ASC, created_at DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    return $pdo->query($sql)->fetchAll();
}

/** Month the Thai academic year starts (June); before it we're still in last year's. */
const ACADEMIC_YEAR_START_MONTH = 6;

/** Current academic year in the Buddhist calendar, e.g. 2569. */
function current_academic_year() {
    $now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
    $year = (int) $now->format('Y');
    if ((int) $now->format('n') < ACADEMIC_YEAR_START_MONTH) {
        $year--;
    }
    return $year + 543;
}

/**
 * student_batches.entry_year / course_years and alumni_batches.graduation_year,
 * added on first use. With an entry year, a cohort's year level is computed
 * and the cohort moves to the alumni list by itself once its course ends.
 */
function ensure_batch_year_columns() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('batch_years_ready') === '1') {
        $done = true;
        return;
    }
    foreach ([
        'ALTER TABLE student_batches ADD COLUMN entry_year INT DEFAULT NULL',
        'ALTER TABLE student_batches ADD COLUMN course_years INT NOT NULL DEFAULT 4',
        'ALTER TABLE alumni_batches ADD COLUMN graduation_year INT DEFAULT NULL',
    ] as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Column already exists.
        }
    }
    set_setting('batch_years_ready', '1');
    $done = true;
}

/** Year level (1..course_years) of a current-student cohort, or null without an entry year. */
function batch_year_level($batch) {
    if (empty($batch['entry_year'])) {
        return null;
    }
    return current_academic_year() - (int) $batch['entry_year'] + 1;
}

/** Academic year in which the cohort graduates (last year of study). */
function batch_graduation_year($batch) {
    return empty($batch['entry_year']) ? null : (int) $batch['entry_year'] + (int) ($batch['course_years'] ?: 4) - 1;
}

/**
 * Moves one current-student cohort (and its photos) to the alumni list.
 * Returns the new alumni batch id.
 */
function move_batch_to_alumni($studentBatchId) {
    global $pdo;
    $batch = get_student_batch($studentBatchId);
    if (!$batch) {
        return null;
    }
    $gradYear = batch_graduation_year($batch) ?: current_academic_year();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO alumni_batches (name, sort_order, graduation_year) VALUES (?, 0, ?)')
            ->execute([$batch['name'], $gradYear]);
        $alumniId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO alumni_photos (batch_id, image_path, caption, sort_order, created_at)
                       SELECT ?, image_path, caption, sort_order, created_at FROM student_photos WHERE batch_id = ?')
            ->execute([$alumniId, $batch['id']]);
        $pdo->prepare('DELETE FROM student_photos WHERE batch_id = ?')->execute([$batch['id']]);
        $pdo->prepare('DELETE FROM student_batches WHERE id = ?')->execute([$batch['id']]);
        $pdo->commit();
        return $alumniId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('move_batch_to_alumni failed: ' . $e->getMessage());
        return null;
    }
}

/** Promotes every cohort whose course has ended. Returns the names moved. */
function promote_graduated_batches() {
    global $pdo;
    ensure_batch_year_columns();
    $stmt = $pdo->prepare('SELECT id, name FROM student_batches WHERE entry_year IS NOT NULL AND entry_year + course_years <= ?');
    // Bind as an integer: some drivers compare a string parameter as text.
    $stmt->bindValue(1, current_academic_year(), PDO::PARAM_INT);
    $stmt->execute();
    $moved = [];
    foreach ($stmt->fetchAll() as $row) {
        if (move_batch_to_alumni($row['id'])) {
            $moved[] = $row['name'];
        }
    }
    return $moved;
}

function get_alumni_batches() {
    global $pdo;
    promote_graduated_batches();
    return $pdo->query('SELECT * FROM alumni_batches ORDER BY sort_order ASC, graduation_year IS NULL ASC, graduation_year DESC, id DESC')->fetchAll();
}

function get_alumni_batch($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM alumni_batches WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_alumni_photos($batchId = null) {
    global $pdo;
    if ($batchId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM alumni_photos WHERE batch_id = ? ORDER BY sort_order ASC, created_at DESC');
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT * FROM alumni_photos ORDER BY batch_id ASC, sort_order ASC, created_at DESC')->fetchAll();
}

function get_student_batches() {
    global $pdo;
    promote_graduated_batches();
    // Year 1 first: newest entry year first, cohorts without a year last.
    return $pdo->query('SELECT * FROM student_batches ORDER BY sort_order ASC, entry_year IS NULL ASC, entry_year DESC, id ASC')->fetchAll();
}

function get_student_batch($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM student_batches WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_student_photos($batchId = null) {
    global $pdo;
    if ($batchId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM student_photos WHERE batch_id = ? ORDER BY sort_order ASC, created_at DESC');
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT * FROM student_photos ORDER BY batch_id ASC, sort_order ASC, created_at DESC')->fetchAll();
}

function get_active_student_service_links() {
    global $pdo;
    return $pdo->query('SELECT * FROM student_service_links WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_all_student_service_links() {
    global $pdo;
    return $pdo->query('SELECT * FROM student_service_links ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_active_teacher_service_links() {
    global $pdo;
    return $pdo->query('SELECT * FROM teacher_service_links WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_all_teacher_service_links() {
    global $pdo;
    return $pdo->query('SELECT * FROM teacher_service_links ORDER BY sort_order ASC, id ASC')->fetchAll();
}

const CLASSROOM_MAX_PHOTOS = 6;

function get_classrooms() {
    global $pdo;
    return $pdo->query('SELECT * FROM classrooms ORDER BY sort_order ASC, id ASC')->fetchAll();
}

/**
 * Creates classroom_photos on first use (no manual migration needed on the
 * live host) and copies each classroom's old single image_path in as its
 * first photo. Guarded by a site setting so it only runs once.
 */
function ensure_classroom_photos_table() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('classroom_photos_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS classroom_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        classroom_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (classroom_id),
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec("INSERT INTO classroom_photos (classroom_id, image_path, sort_order)
        SELECT c.id, c.image_path, 0 FROM classrooms c
        WHERE c.image_path IS NOT NULL AND c.image_path <> ''
          AND NOT EXISTS (SELECT 1 FROM classroom_photos p WHERE p.classroom_id = c.id)");
    set_setting('classroom_photos_ready', '1');
    $done = true;
}

/** [classroom_id => [photo rows...]] for every classroom, in display order. */
function get_classroom_photos() {
    global $pdo;
    ensure_classroom_photos_table();
    $byClassroom = [];
    foreach ($pdo->query('SELECT * FROM classroom_photos ORDER BY sort_order ASC, id ASC') as $row) {
        $byClassroom[$row['classroom_id']][] = $row;
    }
    return $byClassroom;
}

/** Keeps classrooms.image_path pointing at the first photo (used as the cover elsewhere). */
function sync_classroom_cover($classroomId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT image_path FROM classroom_photos WHERE classroom_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
    $stmt->execute([$classroomId]);
    $cover = $stmt->fetchColumn();
    $stmt = $pdo->prepare('UPDATE classrooms SET image_path = ? WHERE id = ?');
    $stmt->execute([$cover ?: null, $classroomId]);
}

function get_active_calendar_links($type) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM calendar_links WHERE calendar_type = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

function get_all_calendar_links($type) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM calendar_links WHERE calendar_type = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

function get_student_year_stats() {
    global $pdo;
    return $pdo->query('SELECT * FROM student_year_stats ORDER BY sort_order ASC, academic_year ASC')->fetchAll();
}

function get_student_year_stat($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM student_year_stats WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_student_work_batches() {
    global $pdo;
    return $pdo->query('SELECT * FROM student_work_batches ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_student_work_batch($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM student_work_batches WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_student_works($batchId = null) {
    global $pdo;
    if ($batchId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM student_works WHERE batch_id = ? ORDER BY sort_order ASC, created_at DESC');
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT * FROM student_works ORDER BY batch_id ASC, sort_order ASC, created_at DESC')->fetchAll();
}

function get_student_work($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM student_works WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Free, no-API keyword search over the site's own content, used by the
 * chatbot widget. Scores each candidate by how many query terms (plus a
 * whole-phrase bonus) appear in its title/text, returns the top matches.
 */
function get_chatbot_search_results($query) {
    global $pdo;
    $query = trim((string) $query);
    if ($query === '') {
        return [];
    }

    // Only pages that still have a real standalone URL (career-intro and the
    // retired classroom-online page are excluded on purpose).
    $pageUrlMap = [
        'home' => '/index.php',
        'course' => '/course.php',
        'history' => '/history.php',
        'subjects' => '/subjects.php',
        'teachers' => '/teachers.php',
        'classroom' => '/classroom.php',
        'students' => '/students.php',
        'alumni' => '/alumni.php',
        'student-services' => '/student-services.php',
        'contact' => '/contact.php',
    ];

    $corpus = [];

    foreach ($pdo->query('SELECT slug, title, subtitle, content FROM pages') as $p) {
        if (!isset($pageUrlMap[$p['slug']])) {
            continue;
        }
        $text = trim(preg_replace('/\s+/u', ' ', (string) $p['subtitle'] . ' ' . strip_tags(mb_substr((string) $p['content'], 0, 4000))));
        $corpus[] = ['title' => $p['title'], 'text' => $text, 'url' => $pageUrlMap[$p['slug']]];
    }

    foreach ($pdo->query('SELECT id, name, position, bio FROM teachers') as $t) {
        $text = trim(((string) $t['position']) . ' ' . strip_tags((string) $t['bio']));
        $corpus[] = ['title' => $t['name'], 'text' => $text, 'url' => '/teacher-profile.php?id=' . $t['id']];
    }

    foreach ($pdo->query('SELECT name, description FROM fields_of_study') as $f) {
        $corpus[] = ['title' => $f['name'], 'text' => (string) $f['description'], 'url' => '/subjects.php'];
    }

    $phone = get_setting('phone');
    $email = get_setting('email');
    $address = get_setting('address');
    if ($phone || $email || $address) {
        $corpus[] = [
            'title' => 'ติดต่อหลักสูตร',
            'text' => trim("ติดต่อ ติดต่อเรา เบอร์โทร โทรศัพท์ เบอร์ $phone อีเมล อีเมล์ $email ที่อยู่ ที่ตั้ง แผนที่ $address"),
            'url' => '/contact.php',
        ];
    }

    $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    $results = [];
    foreach ($corpus as $item) {
        $haystack = $item['title'] . ' ' . $item['text'];
        $score = 0;
        foreach ($terms as $term) {
            if (mb_strlen($term) < 2) {
                continue;
            }
            if (mb_stripos($haystack, $term) !== false) {
                $score++;
            }
        }
        if (mb_stripos($haystack, $query) !== false) {
            $score += 3;
        }
        if ($score > 0) {
            $results[] = ['title' => $item['title'], 'text' => $item['text'], 'url' => $item['url'], 'score' => $score];
        }
    }
    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, 3);
}

/**
 * Derives a page "slug" (matching the $currentSlug each page template sets)
 * from a nav item's URL, so admin-managed menu items still get correct
 * active-state highlighting for ordinary internal links. External links or
 * unusual paths just won't match anything, which is harmless.
 */
function nav_slug_from_url($url) {
    $url = trim((string) $url);
    if ($url === '' || $url === '#') {
        return null; // dropdown-only parent, never matches a page
    }
    if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
        return null; // external link, never matches a local page slug
    }
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    if ($path === '' || $path === 'index.php') {
        return 'home';
    }
    return preg_replace('/\.php$/', '', $path);
}

function get_all_nav_menu_items() {
    global $pdo;
    return $pdo->query('SELECT * FROM nav_menu_items ORDER BY parent_id IS NOT NULL ASC, parent_id ASC, sort_order ASC, id ASC')->fetchAll();
}

/** Builds the nested tree used by the header's main menu from nav_menu_items. */
/**
 * nav_menu_items.new_tab: open the menu link in a new browser tab. Added on
 * first use; the items under "บริการนักศึกษา" start switched on (requested).
 */
function ensure_nav_new_tab_column() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('nav_new_tab_ready') === '1') {
        $done = true;
        return;
    }
    try {
        $pdo->exec('ALTER TABLE nav_menu_items ADD COLUMN new_tab TINYINT(1) NOT NULL DEFAULT 0');
        $parent = $pdo->query("SELECT id FROM nav_menu_items WHERE parent_id IS NULL AND label = 'บริการนักศึกษา' LIMIT 1")->fetchColumn();
        if ($parent) {
            $pdo->prepare('UPDATE nav_menu_items SET new_tab = 1 WHERE parent_id = ?')->execute([$parent]);
        }
    } catch (PDOException $e) {
        // Column already exists.
    }
    set_setting('nav_new_tab_ready', '1');
    $done = true;
}

function get_nav_menu_tree($activeOnly = true) {
    global $pdo;
    ensure_nav_new_tab_column();
    ensure_statistics_page();
    ensure_outstanding_alumni();
    ensure_alumni_hub();
    $sql = 'SELECT * FROM nav_menu_items';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $rows = $pdo->query($sql)->fetchAll();

    $byParent = [];
    foreach ($rows as $row) {
        $byParent[$row['parent_id'] ?? 0][] = $row;
    }

    $build = function ($parentId) use (&$build, $byParent) {
        $items = [];
        foreach ($byParent[$parentId] ?? [] as $row) {
            $children = $build($row['id']);
            $item = [
                'id' => $row['id'],
                'slug' => nav_slug_from_url($row['url']),
                'label' => lang_field($row, 'label'),
                'href' => clean_public_url($row['url'] ?: '#'),
                'new_tab' => !empty($row['new_tab']),
            ];
            if ($children) {
                $item['children'] = $children;
            }
            $items[] = $item;
        }
        return $items;
    };

    return $build(0);
}

/** Flat site navigation - used by the footer quick-links and anywhere a plain list is needed. */
function nav_items() {
    $flat = [];
    $walk = function ($items) use (&$walk, &$flat) {
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                $walk($item['children']);
                continue; // dropdown-only parents aren't real links themselves
            }
            $flat[] = ['slug' => $item['slug'], 'label' => $item['label'], 'href' => $item['href']];
        }
    };
    $walk(get_nav_menu_tree());
    return $flat;
}

/** Nested navigation for the header's main menu - groups related pages under a dropdown. */
/** "/course.php?x=1" -> "/course?x=1", "/index.php" -> "/" when .htaccess rewriting is active. */
function clean_public_url($url) {
    if (!is_string($url) || $url === '' || $url[0] !== '/' || strpos($url, '/admin/') === 0) {
        return $url;
    }
    // Only when .htaccess is active; otherwise make sure links point at the .php files.
    $rewriteOn = !empty($_SERVER['CLEAN_URLS']) || !empty($_SERVER['REDIRECT_CLEAN_URLS']);
    if (!$rewriteOn) {
        return preg_replace('#^/(?!index$)([a-z0-9-]+)(?=$|[?\#])#i', '/$1.php', $url);
    }
    $url = preg_replace('#^/index\.php(?=$|[?\#])#', '/', $url);
    return preg_replace('#^/([a-z0-9-]+)\.php(?=$|[?\#])#i', '/$1', $url);
}

function nav_tree() {
    return get_nav_menu_tree();
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']);
}

function require_admin_login() {
    global $pdo;
    if (!is_admin_logged_in()) {
        header('Location: /admin/index.php');
        exit;
    }
    // With many admin accounts, one may be deleted while its owner is still
    // logged in somewhere - end that session instead of letting it keep editing.
    $stmt = $pdo->prepare('SELECT username FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $username = $stmt->fetchColumn();
    if ($username === false) {
        $_SESSION = [];
        session_destroy();
        header('Location: /admin/index.php');
        exit;
    }
    $_SESSION['admin_username'] = $username;
}

/**
 * Saves an uploaded file ($_FILES[$field]) to OpenDrive when configured (see
 * includes/opendrive.php), otherwise into UPLOAD_DIR; returns the public URL/path or null.
 * $allowedMimeByExt maps lowercase extension => expected MIME type, e.g. ['pdf' => 'application/pdf'].
 */
function handle_file_upload($field, $subdir, array $allowedMimeByExt) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowedMimeByExt)) {
        return null;
    }
    $mime = mime_content_type($_FILES[$field]['tmp_name']);
    if ($mime !== $allowedMimeByExt[$ext]) {
        return null;
    }

    // Preferred storage: OpenDrive (one folder per section). If OpenDrive is
    // unreachable, keep the admin's upload by saving it locally instead and
    // tell them, rather than silently dropping the file.
    if (opendrive_enabled()) {
        try {
            return opendrive_store_file($_FILES[$field]['tmp_name'], $ext, trim((string) $subdir, '/'));
        } catch (Throwable $e) {
            error_log('OpenDrive upload failed, saved locally instead: ' . $e->getMessage());
            $_SESSION['upload_notice'] = 'อัปโหลดไป OpenDrive ไม่สำเร็จ ระบบเก็บไฟล์ไว้บนเซิร์ฟเวอร์แทน (ไฟล์ยังใช้งานได้ตามปกติ)';
        }
    }

    $targetDir = UPLOAD_DIR . ($subdir ? '/' . trim($subdir, '/') : '');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        return null;
    }

    return UPLOAD_URL . ($subdir ? '/' . trim($subdir, '/') : '') . '/' . $filename;
}

/** Saves an uploaded image ($_FILES[$field]) into UPLOAD_DIR, returns the public path or null. */
function handle_image_upload($field, $subdir = '') {
    return handle_file_upload($field, $subdir, [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif',
    ]);
}

/**
 * Saves up to $limit images from a multiple file input (name="field[]").
 * Returns ['paths' => [...], 'skipped' => n] - skipped counts files over the
 * limit or rejected as non-images.
 */
function handle_multi_image_upload($field, $subdir, $limit) {
    $result = ['paths' => [], 'skipped' => 0];
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $result;
    }
    $files = $_FILES[$field];
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if (count($result['paths']) >= $limit) {
            $result['skipped']++;
            continue;
        }
        // Reuse the single-file validation/storage path for each entry.
        $_FILES['__multi_item'] = [
            'name' => $name,
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
        $path = handle_image_upload('__multi_item', $subdir);
        if ($path) {
            $result['paths'][] = $path;
        } else {
            $result['skipped']++;
        }
    }
    unset($_FILES['__multi_item']);
    return $result;
}

/** Saves an uploaded PDF ($_FILES[$field]) into UPLOAD_DIR, returns the public path or null. */
function handle_pdf_upload($field, $subdir = 'documents') {
    return handle_file_upload($field, $subdir, ['pdf' => 'application/pdf']);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function set_flash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function csrf_check() {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $submittedToken = $_POST['csrf_token'] ?? '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

require_once __DIR__ . '/alumni-hub.php';
