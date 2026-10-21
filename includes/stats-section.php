<?php
/**
 * Admission statistics + students by year level as WebGL 3D charts
 * (assets/js/stats-fx.js). Used by statistics.php.
 * Expects $stats (get_stats()) and $studentYearStats (get_student_year_stats()).
 */
?>
<?php if ($stats): ?>
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
<?php if ($stats || $studentYearStats): ?>
<script src="/assets/js/stats-fx.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/stats-fx.js') ?>" defer></script>
<?php endif; ?>
