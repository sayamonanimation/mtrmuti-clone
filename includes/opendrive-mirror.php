<?php
/**
 * Copies files the site keeps on OpenDrive (od.lk links saved anywhere in the
 * database) onto this server under assets/uploads/<section>/ and points the
 * database at the local copy, so the site no longer depends on OpenDrive
 * being reachable.
 *
 * Each file is fetched from its public od.lk link first; if that is blocked
 * (e.g. account suspended) it is downloaded through the API with the site's
 * own OpenDrive login. Every copied link is logged in
 * storage/opendrive_mirror.json (local path => original OpenDrive link).
 */

const OPENDRIVE_MIRROR_BATCH = 4;
const OPENDRIVE_MIRROR_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'];
const OPENDRIVE_LINK_PATTERN = '~https?://(?:www\.)?od\.lk/[dsf]/([A-Za-z0-9_=-]+)/([^\s"\'<>(),\\\\]+)~';

/** Which uploads folder a copy goes into, by the table that uses it. */
const OPENDRIVE_TABLE_SECTIONS = [
    'teachers' => 'teachers',
    'teacher_portfolio' => 'teacher-portfolio',
    'sliders' => 'sliders',
    'posts' => 'posts',
    'pages' => 'posts',
    'gallery' => 'gallery',
    'gallery_albums' => 'gallery',
    'student_photos' => 'students',
    'alumni_photos' => 'alumni',
    'outstanding_alumni' => 'alumni',
    'alumni_members' => 'alumni-hub',
    'alumni_works' => 'alumni-hub',
    'alumni_portfolio_items' => 'alumni-hub',
    'classrooms' => 'classrooms',
    'classroom_photos' => 'classrooms',
    'student_works' => 'student-works',
    'student_work_photos' => 'student-works',
    'fields_of_study' => 'fields',
    'site_settings' => 'branding',
];

/** Every text column in the database as [table, column]. */
function opendrive_text_columns(PDO $pdo) {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $rows = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN ('char','varchar','tinytext','text','mediumtext','longtext')")->fetchAll(PDO::FETCH_NUM);
        return array_map(function ($r) { return [$r[0], $r[1]]; }, $rows);
    }
    $cols = [];
    foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN) as $table) {
        foreach ($pdo->query('PRAGMA table_info(' . opendrive_quote_ident($table) . ')') as $c) {
            if (preg_match('/char|text|clob/i', (string) $c['type'])) {
                $cols[] = [$table, $c['name']];
            }
        }
    }
    return $cols;
}

function opendrive_quote_ident($name) {
    return '`' . str_replace('`', '``', $name) . '`';
}

/**
 * OpenDrive links still used by the site: url => list of "table.column"
 * places, sorted by url so batches are stable between requests.
 */
function opendrive_remote_links(PDO $pdo) {
    $links = [];
    foreach (opendrive_text_columns($pdo) as [$table, $col]) {
        try {
            $values = $pdo->query('SELECT ' . opendrive_quote_ident($col) . ' FROM ' . opendrive_quote_ident($table)
                . ' WHERE ' . opendrive_quote_ident($col) . " LIKE '%od.lk/%'")->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            continue;
        }
        foreach ($values as $value) {
            if (preg_match_all(OPENDRIVE_LINK_PATTERN, (string) $value, $m)) {
                foreach ($m[0] as $url) {
                    $links[$url][$table . '.' . $col] = [$table, $col];
                }
            }
        }
    }
    ksort($links);
    return $links;
}

/** Downloads a public URL into $dest; true only for a real file (not an error page). */
function opendrive_fetch_public($url, $dest) {
    $fp = fopen($dest, 'wb');
    if (!$fp) {
        return false;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 90,
    ]);
    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    fclose($fp);
    if ($ok === false || $code !== 200 || stripos($type, 'text/html') !== false) {
        @unlink($dest);
        return false;
    }
    return true;
}

/** True if $path really is a file of type $ext (guards against saved error pages). */
function opendrive_file_looks_valid($path, $ext) {
    if (!is_file($path) || filesize($path) === 0) {
        return false;
    }
    $head = (string) file_get_contents($path, false, null, 0, 512);
    if ($ext === 'pdf') {
        return strpos($head, '%PDF') === 0;
    }
    if ($ext === 'svg') {
        return stripos($head, '<svg') !== false || stripos($head, '<?xml') !== false;
    }
    return @getimagesize($path) !== false;
}

/**
 * Copies one OpenDrive file to the server and rewrites every place that uses
 * it. Returns the new local URL. Throws RuntimeException on failure.
 * $download (tests only) replaces the network fetch: fn($url, $fileId, $dest): bool
 */
function opendrive_mirror_link(PDO $pdo, $url, array $places, &$client = null, $download = null) {
    if (!preg_match(OPENDRIVE_LINK_PATTERN, $url, $m)) {
        throw new RuntimeException('ลิงก์ไม่ใช่ OpenDrive');
    }
    $fileId = $m[1];
    $name = rawurldecode(basename($m[2]));
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, OPENDRIVE_MIRROR_EXTS, true)) {
        throw new RuntimeException('ไม่รองรับไฟล์ชนิด .' . $ext);
    }
    $first = reset($places);
    $section = OPENDRIVE_TABLE_SECTIONS[$first[0]] ?? 'general';
    $dir = UPLOAD_DIR . '/' . $section;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('สร้างโฟลเดอร์ ' . $section . ' บนเซิร์ฟเวอร์ไม่ได้');
    }
    // Same link always maps to the same file name, so a re-run reuses it.
    $base = preg_replace('/[^A-Za-z0-9_-]/', '', pathinfo($name, PATHINFO_FILENAME)) ?: 'file';
    $file = substr($base, 0, 60) . '-' . substr(sha1($url), 0, 6) . '.' . $ext;
    $target = $dir . '/' . $file;
    $local = UPLOAD_URL . '/' . $section . '/' . $file;

    if (!opendrive_file_looks_valid($target, $ext)) {
        $part = $target . '.part';
        if ($download) {
            $got = $download($url, $fileId, $part);
        } else {
            $got = opendrive_fetch_public($url, $part);
            if (!$got) {
                if (!opendrive_enabled()) {
                    throw new RuntimeException('ลิงก์สาธารณะถูกปิด และยังไม่ได้ตั้งค่าบัญชี OpenDrive');
                }
                if (!$client) {
                    $client = new OpenDriveClient(OD_USERNAME, OD_PASSWORD);
                }
                $client->downloadFile($fileId, $part);
                $got = true;
            }
        }
        if (!$got || !opendrive_file_looks_valid($part, $ext)) {
            @unlink($part);
            throw new RuntimeException('ไฟล์ที่ได้มาไม่ใช่ไฟล์ .' . $ext . ' ที่ถูกต้อง');
        }
        if (!@rename($part, $target)) {
            @unlink($part);
            throw new RuntimeException('บันทึกไฟล์บนเซิร์ฟเวอร์ไม่ได้');
        }
    }

    foreach ($places as [$table, $col]) {
        $q = opendrive_quote_ident($col);
        $stmt = $pdo->prepare('UPDATE ' . opendrive_quote_ident($table) . " SET $q = REPLACE($q, ?, ?) WHERE $q LIKE ?");
        $stmt->execute([$url, $local, '%' . $url . '%']);
    }

    $logFile = SITE_ROOT . '/storage/opendrive_mirror.json';
    $log = is_file($logFile) ? (json_decode((string) file_get_contents($logFile), true) ?: []) : [];
    $log[$local] = $url;
    @file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $local;
}

/**
 * Copies the next batch of OpenDrive files. $skip = how many links at the
 * front of the list already failed this run (they stay in the list).
 * Returns ['copied' => n, 'failed' => [[url, message]], 'remaining' => n].
 */
function opendrive_mirror_batch(PDO $pdo, $skip = 0, $limit = OPENDRIVE_MIRROR_BATCH) {
    @set_time_limit(300);
    $links = opendrive_remote_links($pdo);
    $client = null;
    $copied = 0;
    $failed = [];
    foreach (array_slice($links, max(0, (int) $skip), $limit, true) as $url => $places) {
        try {
            opendrive_mirror_link($pdo, $url, $places, $client);
            $copied++;
        } catch (Throwable $e) {
            $failed[] = [$url, $e->getMessage()];
        }
    }
    return ['copied' => $copied, 'failed' => $failed, 'remaining' => count(opendrive_remote_links($pdo))];
}
