<?php
$currentSlug = 'activity-calendar';
require_once __DIR__ . '/includes/header.php';

$links = get_active_calendar_links('activity');
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(t('nav.activity_calendar')) ?></h1>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <?php if ($links): ?>
        <div class="link-list">
            <?php foreach ($links as $l): ?>
            <a class="link-list-item" href="<?= h($l['url']) ?>" target="_blank" rel="noopener">
                <span><?= h(lang_field($l, 'label')) ?></span>
                <span class="link-list-arrow">&rarr;</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p><?= h(t('calendar.no_links')) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
