<?php
/**
 * สถิติการรับนักศึกษา: per academic year, the admission plan and the number
 * of students who registered, split by background (ม.6 / ปวช.). Percentages
 * (registered ÷ plan) and totals are worked out here, never stored.
 *
 * Managed in admin/stats.php, shown by includes/stats-section.php.
 * The table is created on first use.
 */

/** How many academic years the public comparison shows side by side. */
const ADMISSION_COMPARE_YEARS = 3;

function ensure_admission_stats() {
    global $pdo;
    static $done = false;
    if ($done || get_setting('admission_stats_ready') === '1') {
        $done = true;
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS admission_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        academic_year INT NOT NULL UNIQUE,
        plan_m6 INT NOT NULL DEFAULT 0,
        plan_voc INT NOT NULL DEFAULT 0,
        reg_m6 INT NOT NULL DEFAULT 0,
        reg_voc INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    set_setting('admission_stats_ready', '1');
    $done = true;
}

/** Percentage of $part in $whole with one decimal, or null when $whole is 0. */
function admission_pct($part, $whole) {
    return $whole > 0 ? round($part / $whole * 100, 1) : null;
}

function admission_pct_text($pct) {
    return $pct === null ? '–' : rtrim(rtrim(number_format($pct, 1), '0'), '.') . '%';
}

/** Rows oldest → newest, with totals and percentages added. */
function get_admission_stats() {
    global $pdo;
    ensure_admission_stats();
    $rows = $pdo->query('SELECT * FROM admission_stats ORDER BY academic_year ASC')->fetchAll();
    foreach ($rows as &$r) {
        foreach (['plan_m6', 'plan_voc', 'reg_m6', 'reg_voc', 'academic_year'] as $k) {
            $r[$k] = (int) $r[$k];
        }
        $r['plan_total'] = $r['plan_m6'] + $r['plan_voc'];
        $r['reg_total'] = $r['reg_m6'] + $r['reg_voc'];
        $r['pct_m6'] = admission_pct($r['reg_m6'], $r['plan_m6']);
        $r['pct_voc'] = admission_pct($r['reg_voc'], $r['plan_voc']);
        $r['pct_total'] = admission_pct($r['reg_total'], $r['plan_total']);
    }
    return $rows;
}
