<?php
$currentSlug = 'student-works';
require_once __DIR__ . '/includes/header.php';

$batches = get_student_work_batches();
$worksByBatch = [];
foreach ($batches as $batch) {
    $worksByBatch[$batch['id']] = get_student_works($batch['id']);
}
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(t('nav.student_works')) ?></h1>
    </div>
</div>
<div class="page-content">
    <?php if ($batches): ?>
    <div class="container">
        <?php foreach ($batches as $batch): ?>
            <?php $works = $worksByBatch[$batch['id']]; ?>
            <?php if (!$works): ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="batch-section">
                <h2><?= h($batch['name']) ?></h2>
                <div class="card-grid">
                    <?php foreach ($works as $w): ?>
                    <?php $workTag = $w['link_url'] ? 'a' : 'div'; ?>
                    <<?= $workTag ?> class="card<?= $w['link_url'] ? ' card-link' : '' ?>"<?php if ($w['link_url']): ?> href="<?= h($w['link_url']) ?>" target="_blank" rel="noopener"<?php endif; ?>>
                        <img src="<?= h($w['image_path']) ?>" alt="<?= h(lang_field($w, 'title')) ?>">
                        <div class="card-body">
                            <h3><?= h(lang_field($w, 'title')) ?></h3>
                            <?php if (lang_field($w, 'description')): ?><p><?= h(lang_field($w, 'description')) ?></p><?php endif; ?>
                            <?php if ($w['student_name']): ?><p class="student-work-author"><?= h($w['student_name']) ?></p><?php endif; ?>
                        </div>
                    </<?= $workTag ?>>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="container"><p><?= h(t('student_works.no_works')) ?></p></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
