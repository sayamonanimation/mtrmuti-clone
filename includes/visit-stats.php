<?php
/**
 * Visitor statistics for the admin "สถิติผู้เข้าชม" page.
 *
 * Every public page view (includes/header.php) is counted, except bots,
 * prefetches and signed-in admins. Nothing personal is stored: a visitor is
 * a random cookie id (mt_vid, 1 year), saved only as a short hash.
 *
 *   visit_daily   (date, page)    -> page views + page title
 *   visit_uniques (date, visitor) -> one row per visitor per day + device
 *
 * Dates are Bangkok time. Counting must never break a page, so every
 * failure is swallowed.
 */

const VISIT_TZ = 'Asia/Bangkok';
const VISIT_STATS_START = '2026-11-05';   // official start (mtrmuti.com went live); stats never show earlier days
const VISIT_BOT_PATTERN = '/bot|crawl|spider|slurp|scrap|fetch|preview|monitor|uptime|headless|lighthouse|pagespeed|facebookexternalhit|whatsapp|line-poker|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|php\//i';

function visit_today($modify = null) {
    $d = new DateTime('now', new DateTimeZone(VISIT_TZ));
    if ($modify) {
        $d->modify($modify);
    }
    return $d->format('Y-m-d');
}

function ensure_visit_stats() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('visit_stats_ready') === '1') {
        $done = true;
        return;
    }
    $engine = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
    $pdo->exec('CREATE TABLE IF NOT EXISTS visit_daily (
        visit_date DATE NOT NULL,
        path VARCHAR(191) NOT NULL,
        title VARCHAR(255) DEFAULT NULL,
        views INT NOT NULL DEFAULT 0,
        PRIMARY KEY (visit_date, path)
    )' . $engine);
    $pdo->exec('CREATE TABLE IF NOT EXISTS visit_uniques (
        visit_date DATE NOT NULL,
        visitor CHAR(16) NOT NULL,
        device VARCHAR(10) NOT NULL DEFAULT \'desktop\',
        PRIMARY KEY (visit_date, visitor)
    )' . $engine);
    set_setting('visit_stats_since', visit_today());
    set_setting('visit_stats_ready', '1');
    $done = true;
}

/** First day the statistics cover (Y-m-d). */
function visit_stats_since() {
    $saved = (string) get_setting('visit_stats_since', VISIT_STATS_START);
    return min($saved !== '' ? $saved : VISIT_STATS_START, VISIT_STATS_START);
}

/** Page key: clean path, plus ?id= for profile/album style pages. */
function visit_page_key() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $path = preg_replace('/\.php$/', '', $path);
    $path = rtrim($path, '/');
    if ($path === '' || $path === '/index') {
        $path = '/';
    }
    if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
        $path .= '?id=' . $_GET['id'];
    } elseif (isset($_GET['slug']) && preg_match('/^[a-z0-9-]{1,60}$/', (string) $_GET['slug'])) {
        $path .= '?slug=' . $_GET['slug'];
    }
    return mb_substr($path, 0, 191);
}

function visit_should_count() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || is_admin_logged_in()) {
        return false;
    }
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match(VISIT_BOT_PATTERN, $ua)) {
        return false;
    }
    $purpose = strtolower(($_SERVER['HTTP_SEC_PURPOSE'] ?? '') . ($_SERVER['HTTP_PURPOSE'] ?? '') . ($_SERVER['HTTP_X_MOZ'] ?? ''));
    return strpos($purpose, 'prefetch') === false && strpos($purpose, 'prerender') === false;
}

/** Counts this page view. Called from includes/header.php before any output. */
function track_visit($title) {
    global $pdo;
    try {
        if (!visit_should_count()) {
            return;
        }
        ensure_visit_stats();
        $vid = (string) ($_COOKIE['mt_vid'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $vid)) {
            if (headers_sent()) {
                // Can't set the cookie: fall back to an anonymous same-day fingerprint.
                $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
                $vid = md5($ip . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . visit_today());
            } else {
                $vid = bin2hex(random_bytes(16));
                setcookie('mt_vid', $vid, ['expires' => time() + 365 * 86400, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https', 'httponly' => true, 'samesite' => 'Lax']);
            }
        }
        $visitor = substr(hash('sha256', $vid), 0, 16);
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $device = preg_match('/iPad|Tablet/i', $ua) ? 'tablet' : (preg_match('/Mobi|Android|iPhone/i', $ua) ? 'mobile' : 'desktop');
        $date = visit_today();
        $title = mb_substr(trim((string) $title), 0, 255);
        $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';

        $pdo->prepare($mysql
            ? 'INSERT INTO visit_daily (visit_date, path, title, views) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE views = views + 1, title = VALUES(title)'
            : 'INSERT INTO visit_daily (visit_date, path, title, views) VALUES (?, ?, ?, 1) ON CONFLICT (visit_date, path) DO UPDATE SET views = views + 1, title = excluded.title')
            ->execute([$date, visit_page_key(), $title]);
        $pdo->prepare(($mysql ? 'INSERT IGNORE' : 'INSERT OR IGNORE') . ' INTO visit_uniques (visit_date, visitor, device) VALUES (?, ?, ?)')
            ->execute([$date, $visitor, $device]);
    } catch (Throwable $e) {
        error_log('track_visit: ' . $e->getMessage());
    }
}

/** ['visitors' => distinct visitors, 'views' => page views] between two dates (inclusive). */
function visit_totals($from, $to) {
    global $pdo;
    ensure_visit_stats();
    $v = $pdo->prepare('SELECT COUNT(DISTINCT visitor) FROM visit_uniques WHERE visit_date BETWEEN ? AND ?');
    $v->execute([$from, $to]);
    $p = $pdo->prepare('SELECT COALESCE(SUM(views), 0) FROM visit_daily WHERE visit_date BETWEEN ? AND ?');
    $p->execute([$from, $to]);
    return ['visitors' => (int) $v->fetchColumn(), 'views' => (int) $p->fetchColumn()];
}

/**
 * Chart series from $from to today: list of ['key', 'label', 'visitors', 'views'].
 * $byMonth groups by month (for long ranges); visitors per bucket are distinct.
 */
function visit_series($from, $byMonth = false) {
    global $pdo;
    ensure_visit_stats();
    $to = visit_today();
    $len = $byMonth ? 7 : 10;
    $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    $bucket = $byMonth ? ($mysql ? "DATE_FORMAT(visit_date, '%Y-%m')" : "strftime('%Y-%m', visit_date)") : 'visit_date';

    $visitors = [];
    $stmt = $pdo->prepare("SELECT $bucket AS k, COUNT(DISTINCT visitor) AS n FROM visit_uniques WHERE visit_date BETWEEN ? AND ? GROUP BY k");
    $stmt->execute([$from, $to]);
    foreach ($stmt as $r) {
        $visitors[substr((string) $r['k'], 0, $len)] = (int) $r['n'];
    }
    $views = [];
    $stmt = $pdo->prepare("SELECT $bucket AS k, SUM(views) AS n FROM visit_daily WHERE visit_date BETWEEN ? AND ? GROUP BY k");
    $stmt->execute([$from, $to]);
    foreach ($stmt as $r) {
        $views[substr((string) $r['k'], 0, $len)] = (int) $r['n'];
    }

    $thMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $out = [];
    $d = new DateTime($from, new DateTimeZone(VISIT_TZ));
    $end = new DateTime($to, new DateTimeZone(VISIT_TZ));
    if ($byMonth) {
        $d->modify('first day of this month');
    }
    while ($d <= $end) {
        $key = $d->format($byMonth ? 'Y-m' : 'Y-m-d');
        $m = (int) $d->format('n');
        $label = $byMonth ? $thMonths[$m] . ' ' . substr((string) ((int) $d->format('Y') + 543), 2) : (int) $d->format('j') . ' ' . $thMonths[$m];
        $out[] = ['key' => $key, 'label' => $label, 'visitors' => $visitors[$key] ?? 0, 'views' => $views[$key] ?? 0];
        $d->modify($byMonth ? '+1 month' : '+1 day');
    }
    return $out;
}

/** Most viewed pages: list of ['path', 'title', 'views']. */
function visit_top_pages($from, $limit = 15) {
    global $pdo;
    ensure_visit_stats();
    $stmt = $pdo->prepare('SELECT path, MAX(title) AS title, SUM(views) AS views FROM visit_daily
        WHERE visit_date BETWEEN ? AND ? GROUP BY path ORDER BY views DESC LIMIT ' . (int) $limit);
    $stmt->execute([$from, visit_today()]);
    return $stmt->fetchAll();
}

/** Distinct visitors by device in the range: ['mobile' => n, 'desktop' => n, 'tablet' => n]. */
function visit_devices($from) {
    global $pdo;
    ensure_visit_stats();
    $out = ['mobile' => 0, 'desktop' => 0, 'tablet' => 0];
    $stmt = $pdo->prepare('SELECT device, COUNT(DISTINCT visitor) AS n FROM visit_uniques WHERE visit_date BETWEEN ? AND ? GROUP BY device');
    $stmt->execute([$from, visit_today()]);
    foreach ($stmt as $r) {
        $out[$r['device']] = (int) $r['n'];
    }
    return $out;
}
