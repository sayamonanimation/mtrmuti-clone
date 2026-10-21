<?php
$currentSlug = 'statistics';
require_once __DIR__ . '/includes/header.php';

ensure_statistics_page();
$page = get_page('statistics');
$stats = get_stats();
$studentYearStats = get_student_year_stats();
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content stats-page">
    <?php if (trim(strip_tags((string) lang_field($page, 'content'))) !== ''): ?>
    <div class="container prose" style="margin-bottom:10px;"><?= lang_field($page, 'content') ?></div>
    <?php endif; ?>
    <?php require __DIR__ . '/includes/stats-section.php'; ?>
    <?php if (!$stats && !$studentYearStats): ?>
    <div class="container"><p><?= h(t('home.stats_empty')) ?></p></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
