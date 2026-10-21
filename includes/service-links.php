<?php
/**
 * บริการนักศึกษา › ข้อมูลทั่วไป: headings (student_service_sections), each
 * with any number of links (student_service_items). Managed in
 * admin/student-service-links.php, shown on student-services.php.
 *
 * Tables are created on first use. Real links from the old flat
 * student_service_links list are copied into a first heading.
 */

function ensure_student_service_sections() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('student_service_sections_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS student_service_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        title_en VARCHAR(255) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS student_service_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        label VARCHAR(255) NOT NULL,
        label_en VARCHAR(255) DEFAULT NULL,
        url VARCHAR(500) NOT NULL,
        description VARCHAR(500) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        INDEX (section_id),
        FOREIGN KEY (section_id) REFERENCES student_service_sections(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    try {
        $old = $pdo->query("SELECT label, label_en, url, sort_order FROM student_service_links
            WHERE is_active = 1 AND url <> '' AND url <> '#' ORDER BY sort_order, id")->fetchAll();
    } catch (PDOException $e) {
        $old = []; // old table not on this install
    }
    if ($old && !$pdo->query('SELECT COUNT(*) FROM student_service_sections')->fetchColumn()) {
        $pdo->prepare('INSERT INTO student_service_sections (title, title_en, sort_order) VALUES (?, ?, 0)')
            ->execute(['ระบบบริการนักศึกษา', 'Student Service Systems']);
        $sectionId = (int) $pdo->lastInsertId();
        $insert = $pdo->prepare('INSERT INTO student_service_items (section_id, label, label_en, url, sort_order) VALUES (?, ?, ?, ?, ?)');
        foreach ($old as $row) {
            $insert->execute([$sectionId, $row['label'], $row['label_en'], $row['url'], (int) $row['sort_order']]);
        }
    }
    set_setting('student_service_sections_ready', '1');
    $done = true;
}

/** Headings in display order, each with an 'items' list of its links. */
function get_student_service_sections($activeOnly = true) {
    global $pdo;
    ensure_student_service_sections();
    $sections = $pdo->query('SELECT * FROM student_service_sections'
        . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order ASC, id ASC')->fetchAll();
    $items = [];
    foreach ($pdo->query('SELECT * FROM student_service_items ORDER BY sort_order ASC, id ASC') as $row) {
        $items[$row['section_id']][] = $row;
    }
    foreach ($sections as &$section) {
        $section['items'] = $items[$section['id']] ?? [];
    }
    return $sections;
}
