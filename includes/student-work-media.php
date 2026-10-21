<?php
/**
 * ผลงานนักศึกษา: each work has several photos (student_work_photos) and
 * several links (student_work_links). student_works.image_path / link_url
 * are kept pointing at the first photo / link so older code still works.
 *
 * Tables are created on first use and existing single photos/links copied in.
 */

const STUDENT_WORK_MAX_PHOTOS = 20;
const STUDENT_WORK_MAX_LINKS = 10;

function ensure_student_work_media() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('student_work_media_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS student_work_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        work_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (work_id),
        FOREIGN KEY (work_id) REFERENCES student_works(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS student_work_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        work_id INT NOT NULL,
        label VARCHAR(150) DEFAULT NULL,
        url VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        INDEX (work_id),
        FOREIGN KEY (work_id) REFERENCES student_works(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec("INSERT INTO student_work_photos (work_id, image_path, sort_order)
        SELECT w.id, w.image_path, 0 FROM student_works w
        WHERE w.image_path IS NOT NULL AND w.image_path <> ''
          AND NOT EXISTS (SELECT 1 FROM student_work_photos p WHERE p.work_id = w.id)");
    $pdo->exec("INSERT INTO student_work_links (work_id, label, url, sort_order)
        SELECT w.id, NULL, w.link_url, 0 FROM student_works w
        WHERE w.link_url IS NOT NULL AND w.link_url <> ''
          AND NOT EXISTS (SELECT 1 FROM student_work_links l WHERE l.work_id = w.id)");
    set_setting('student_work_media_ready', '1');
    $done = true;
}

function get_student_work_photos($workId) {
    global $pdo;
    ensure_student_work_media();
    $stmt = $pdo->prepare('SELECT * FROM student_work_photos WHERE work_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([(int) $workId]);
    return $stmt->fetchAll();
}

function get_student_work_links($workId) {
    global $pdo;
    ensure_student_work_media();
    $stmt = $pdo->prepare('SELECT * FROM student_work_links WHERE work_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([(int) $workId]);
    return $stmt->fetchAll();
}

/** ['photos' => [work_id => rows], 'links' => [work_id => rows]] for every work. */
function get_all_student_work_media() {
    global $pdo;
    ensure_student_work_media();
    $media = ['photos' => [], 'links' => []];
    foreach ($pdo->query('SELECT * FROM student_work_photos ORDER BY sort_order ASC, id ASC') as $row) {
        $media['photos'][$row['work_id']][] = $row;
    }
    foreach ($pdo->query('SELECT * FROM student_work_links ORDER BY sort_order ASC, id ASC') as $row) {
        $media['links'][$row['work_id']][] = $row;
    }
    return $media;
}

/** Keeps student_works.image_path / link_url on the first photo / link. */
function sync_student_work_cover($workId) {
    global $pdo;
    $photos = get_student_work_photos($workId);
    $links = get_student_work_links($workId);
    $pdo->prepare('UPDATE student_works SET image_path = ?, link_url = ? WHERE id = ?')
        ->execute([$photos[0]['image_path'] ?? '', $links[0]['url'] ?? null, (int) $workId]);
}

/** Link rows from the form's link_label[] / link_url[] fields, empty rows dropped. */
function student_work_links_from_post() {
    $labels = (array) ($_POST['link_label'] ?? []);
    $urls = (array) ($_POST['link_url'] ?? []);
    $links = [];
    foreach ($urls as $i => $url) {
        $url = normalize_url(trim((string) $url));
        if ($url === '' || count($links) >= STUDENT_WORK_MAX_LINKS) {
            continue;
        }
        $links[] = ['label' => mb_substr(trim((string) ($labels[$i] ?? '')), 0, 150), 'url' => mb_substr($url, 0, 255)];
    }
    return $links;
}

function save_student_work_links($workId, array $links) {
    global $pdo;
    $pdo->prepare('DELETE FROM student_work_links WHERE work_id = ?')->execute([(int) $workId]);
    $insert = $pdo->prepare('INSERT INTO student_work_links (work_id, label, url, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($links as $i => $link) {
        $insert->execute([(int) $workId, $link['label'] ?: null, $link['url'], $i]);
    }
}

function add_student_work_photos($workId, array $paths) {
    global $pdo;
    $next = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM student_work_photos WHERE work_id = ?');
    $next->execute([(int) $workId]);
    $order = (int) $next->fetchColumn();
    $insert = $pdo->prepare('INSERT INTO student_work_photos (work_id, image_path, sort_order) VALUES (?, ?, ?)');
    foreach ($paths as $path) {
        $insert->execute([(int) $workId, $path, $order++]);
    }
}
