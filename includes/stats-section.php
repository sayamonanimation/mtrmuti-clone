<?php
/**
 * Admission statistics + students by year level as WebGL 3D charts
 * (assets/js/stats-fx.js). Used by statistics.php.
 * Expects $admission (get_admission_stats()), $stats (old flat get_stats(),
 * shown only until the new admission data exists) and $studentYearStats.
 */
?>
<?php if ($admission): ?>
<?php
// Comparison window: ADMISSION_COMPARE_YEARS years ending at the chosen year (?y=).
$admYears = array_column($admission, 'academic_year');
$admEnd = in_array((int) ($_GET['y'] ?? 0), $admYears, true) ? (int) $_GET['y'] : end($admYears);
$admWindow = array_values(array_filter($admission, fn($r) => $r['academic_year'] <= $admEnd && $r['academic_year'] > $admEnd - ADMISSION_COMPARE_YEARS));
$admLatest = end($admWindow);
$admPrevRow = null;
foreach ($admission as $r) {
    if ($r['academic_year'] < $admLatest['academic_year']) {
        $admPrevRow = $r;
    }
}
$admChange = $admPrevRow && $admPrevRow['reg_total'] > 0 ? round(($admLatest['reg_total'] - $admPrevRow['reg_total']) / $admPrevRow['reg_total'] * 100) : null;
$admColors = ['#8f4a3f', '#ff5a3c', '#7d6a66', '#ffb199'];
$admSeries = ['แผนรับ ม.6', 'ขึ้นทะเบียน ม.6', 'แผนรับ ปวช.', 'ขึ้นทะเบียน ปวช.'];
$admChart = array_map(fn($r) => ['year' => (string) $r['academic_year'], 'counts' => [$r['plan_m6'], $r['reg_m6'], $r['plan_voc'], $r['reg_voc']]], $admWindow);
$admGroups = [
    ['label' => 'ม.6', 'plan' => 'plan_m6', 'reg' => 'reg_m6', 'pct' => 'pct_m6'],
    ['label' => 'ปวช.', 'plan' => 'plan_voc', 'reg' => 'reg_voc', 'pct' => 'pct_voc'],
    ['label' => 'รวม', 'plan' => 'plan_total', 'reg' => 'reg_total', 'pct' => 'pct_total', 'total' => true],
];
?>
<section class="adm-section" id="admission">
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.stats_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <?php if (count($admYears) > 1): ?>
        <form method="get" action="#admission" class="adm-picker">
            <label>เปรียบเทียบ <?= ADMISSION_COMPARE_YEARS ?> ปี ถึงปีการศึกษา
                <select name="y" onchange="this.form.submit()">
                    <?php foreach (array_reverse($admYears) as $y): ?><option value="<?= $y ?>"<?= $y === $admEnd ? ' selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
                </select>
            </label>
            <noscript><button class="btn" type="submit">แสดง</button></noscript>
        </form>
        <?php endif; ?>
        <div class="stats3d" id="admissionStats3d"
             data-stats="<?= h(json_encode($admChart)) ?>"
             data-colors="<?= h(json_encode($admColors)) ?>"
             data-series-labels="<?= h(json_encode($admSeries, JSON_UNESCAPED_UNICODE)) ?>"
             data-split="2"
             data-subgroup-labels="<?= h(json_encode(['ม.6', 'ปวช.'], JSON_UNESCAPED_UNICODE)) ?>"
             data-year-label="<?= h(t('home.student_stats_academic_year')) ?>"
             data-unit="<?= h(t('home.student_stats_people')) ?>">
            <div class="stats3d-chips stats3d-chips-4">
                <div class="stats3d-chip stats3d-chip-total">
                    <span class="stats3d-chip-label">ขึ้นทะเบียน (ปีการศึกษา <?= $admLatest['academic_year'] ?>)</span>
                    <span class="stats3d-chip-num" data-count="<?= $admLatest['reg_total'] ?>"><?= $admLatest['reg_total'] ?></span>
                </div>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label">แผนรับ</span>
                    <span class="stats3d-chip-num" data-count="<?= $admLatest['plan_total'] ?>"><?= $admLatest['plan_total'] ?></span>
                </div>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label">ร้อยละเทียบแผนรับ</span>
                    <span class="stats3d-chip-num"><?= admission_pct_text($admLatest['pct_total']) ?></span>
                </div>
                <?php if ($admChange !== null): ?>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label">เทียบปี <?= $admPrevRow['academic_year'] ?></span>
                    <span class="stats3d-chip-num <?= $admChange >= 0 ? 'is-up' : 'is-down' ?>" data-count="<?= $admChange ?>" data-suffix="%" data-signed><?= ($admChange > 0 ? '+' : '') . $admChange ?>%</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="adm-legend"><?php foreach ($admSeries as $i => $label): ?><span><i style="background:<?= $admColors[$i] ?>"></i><?= h($label) ?></span><?php endforeach; ?></div>
            <div class="stats3d-stage">
                <canvas class="stats3d-canvas" aria-hidden="true"></canvas>
                <div class="stats3d-labels" aria-hidden="true"></div>
                <div class="stats3d-tip" hidden></div>
            </div>
        </div>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <caption>เปรียบเทียบแผนรับและนักศึกษาที่ขึ้นทะเบียน ปีการศึกษา <?= $admWindow[0]['academic_year'] ?><?= count($admWindow) > 1 ? '–' . $admLatest['academic_year'] : '' ?></caption>
                <thead>
                    <tr><th rowspan="2">พื้นฐาน</th><?php foreach ($admWindow as $r): ?><th colspan="3">ปีการศึกษา <?= $r['academic_year'] ?></th><?php endforeach; ?></tr>
                    <tr><?php foreach ($admWindow as $r): ?><th>แผนรับ</th><th>ขึ้นทะเบียน</th><th>ร้อยละ</th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($admGroups as $g): ?>
                    <tr<?= !empty($g['total']) ? ' class="adm-total-row"' : '' ?>>
                        <th><?= h($g['label']) ?></th>
                        <?php foreach ($admWindow as $r): ?>
                        <td><?= number_format($r[$g['plan']]) ?></td>
                        <td><?= number_format($r[$g['reg']]) ?></td>
                        <td class="adm-pct"><?= admission_pct_text($r[$g['pct']]) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="adm-note">ร้อยละ = จำนวนนักศึกษาที่ขึ้นทะเบียน ÷ แผนรับนักศึกษา × 100</p>
    </div>
</section>
<?php elseif ($stats): ?>
<?php
$admissionData = array_map(function ($row) {
    return ['year' => (string) $row['year'], 'counts' => [(int) $row['count']], 'label' => (string) $row['label']];
}, $stats);
$admissionCounts = array_column(array_column($admissionData, 'counts'), 0);
$admissionLatest = end($admissionData);
$admissionPrev = count($admissionCounts) > 1 ? $admissionCounts[count($admissionCounts) - 2] : null;
$admissionChange = $admissionPrev ? round(($admissionLatest['counts'][0] - $admissionPrev) / $admissionPrev * 100) : null;
?>
<section>
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.stats_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="stats3d" id="admissionStats3d"
             data-stats="<?= h(json_encode($admissionData, JSON_UNESCAPED_UNICODE)) ?>"
             data-colors="<?= h(json_encode(['#ff5a3c'])) ?>"
             data-year-label="<?= h(t('home.stats_year_prefix')) ?>"
             data-unit="<?= h(t('home.student_stats_people')) ?>">
            <div class="stats3d-chips stats3d-chips-4">
                <div class="stats3d-chip stats3d-chip-total">
                    <span class="stats3d-chip-label"><?= h(t('home.stats_latest')) ?> (<?= h(t('home.stats_year_prefix')) ?> <?= h($admissionLatest['year']) ?>)</span>
                    <span class="stats3d-chip-num" data-count="<?= $admissionLatest['counts'][0] ?>"><?= $admissionLatest['counts'][0] ?></span>
                </div>
                <?php if ($admissionChange !== null): ?>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label"><?= h(t('home.stats_change')) ?></span>
                    <span class="stats3d-chip-num <?= $admissionChange >= 0 ? 'is-up' : 'is-down' ?>" data-count="<?= $admissionChange ?>" data-suffix="%" data-signed><?= ($admissionChange > 0 ? '+' : '') . $admissionChange ?>%</span>
                </div>
                <?php endif; ?>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label"><?= h(t('home.stats_average')) ?></span>
                    <span class="stats3d-chip-num" data-count="<?= round(array_sum($admissionCounts) / count($admissionCounts)) ?>"><?= round(array_sum($admissionCounts) / count($admissionCounts)) ?></span>
                </div>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label"><?= h(t('home.stats_total_all')) ?> (<?= count($admissionCounts) ?> <?= h(t('home.stats_years')) ?>)</span>
                    <span class="stats3d-chip-num" data-count="<?= array_sum($admissionCounts) ?>"><?= array_sum($admissionCounts) ?></span>
                </div>
            </div>
            <div class="stats3d-stage">
                <canvas class="stats3d-canvas" aria-hidden="true"></canvas>
                <div class="stats3d-labels" aria-hidden="true"></div>
                <div class="stats3d-tip" hidden></div>
                <table class="stats3d-table">
                    <thead><tr><th><?= h(t('home.stats_year_prefix')) ?></th><th><?= h(t('home.stats_heading')) ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($stats as $s): ?>
                        <tr><th><?= h($s['year']) ?></th><td><?= h($s['count']) ?> <?= h($s['label']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($studentYearStats): ?>
<?php
$statsData = array_map(function ($row) {
    return [
        'year' => (string) $row['academic_year'],
        'counts' => [(int) $row['year1_count'], (int) $row['year2_count'], (int) $row['year3_count'], (int) $row['year4_count']],
    ];
}, $studentYearStats);
$latestStats = end($statsData);
$levelColors = ['#ff5a3c', '#c81e0c', '#ffa07f', '#8f7d7a'];
?>
<section>
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.student_stats_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="stats3d" id="stats3d"
             data-stats="<?= h(json_encode($statsData)) ?>"
             data-colors="<?= h(json_encode($levelColors)) ?>"
             data-series-labels="<?= h(json_encode(array_map(function ($i) { return t('home.student_stats_year') . ' ' . $i; }, [1, 2, 3, 4]), JSON_UNESCAPED_UNICODE)) ?>"
             data-year-label="<?= h(t('home.student_stats_academic_year')) ?>"
             data-unit="<?= h(t('home.student_stats_people')) ?>">
            <div class="stats3d-chips">
                <div class="stats3d-chip stats3d-chip-total">
                    <span class="stats3d-chip-label"><?= h(t('home.student_stats_total')) ?> (<?= h(t('home.student_stats_academic_year')) ?> <?= h($latestStats['year']) ?>)</span>
                    <span class="stats3d-chip-num" data-count="<?= array_sum($latestStats['counts']) ?>"><?= array_sum($latestStats['counts']) ?></span>
                </div>
                <?php foreach ($latestStats['counts'] as $i => $count): ?>
                <div class="stats3d-chip">
                    <span class="stats3d-chip-label"><i style="background:<?= $levelColors[$i] ?>"></i><?= h(t('home.student_stats_year')) ?> <?= $i + 1 ?></span>
                    <span class="stats3d-chip-num" data-count="<?= $count ?>"><?= $count ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="stats3d-stage">
                <canvas class="stats3d-canvas" aria-hidden="true"></canvas>
                <div class="stats3d-labels" aria-hidden="true"></div>
                <div class="stats3d-tip" hidden></div>
                <!-- Fallback / screen-reader table, replaced visually by the WebGL chart -->
                <table class="stats3d-table">
                    <thead><tr><th><?= h(t('home.student_stats_academic_year')) ?></th><?php for ($i = 1; $i <= 4; $i++): ?><th><?= h(t('home.student_stats_year')) ?> <?= $i ?></th><?php endfor; ?></tr></thead>
                    <tbody>
                        <?php foreach ($statsData as $row): ?>
                        <tr><th><?= h($row['year']) ?></th><?php foreach ($row['counts'] as $count): ?><td><?= $count ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php if ($admission || $stats || $studentYearStats): ?>
<script src="/assets/js/stats-fx.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/stats-fx.js') ?>" defer></script>
<?php endif; ?>
