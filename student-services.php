<?php
$currentSlug = 'student-services';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/simple-page.php';
require_once __DIR__ . '/includes/service-links.php';

$serviceSections = array_filter(get_student_service_sections(), fn($s) => $s['items']);
?>
<?php if ($serviceSections): ?>
<div class="page-content svc-page">
    <div class="container">
        <?php foreach ($serviceSections as $s): ?>
        <section class="svc-block">
            <h2><?= h(lang_field($s, 'title')) ?></h2>
            <div class="link-list">
                <?php foreach ($s['items'] as $l): ?>
                <?php $external = preg_match('#^https?://#i', $l['url']); ?>
                <a class="link-list-item" href="<?= h($l['url']) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>>
                    <span><?= h(lang_field($l, 'label')) ?><?php if ($l['description']): ?><small class="svc-desc"><?= h($l['description']) ?></small><?php endif; ?></span>
                    <span class="link-list-arrow"><?= $external ? '↗' : '&rarr;' ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
