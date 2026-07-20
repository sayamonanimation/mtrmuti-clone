<?php
$currentSlug = 'alumni';
require_once __DIR__ . '/includes/header.php';

$page = get_page('alumni');
$batches = get_alumni_batches();
$photosByBatch = [];
foreach ($batches as $batch) {
    $photosByBatch[$batch['id']] = get_alumni_photos($batch['id']);
}
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container prose">
        <?= lang_field($page, 'content') ?>
    </div>

    <?php if ($batches): ?>
    <div class="container">
        <?php foreach ($batches as $batch): ?>
            <?php $photos = $photosByBatch[$batch['id']]; ?>
            <?php if (!$photos): ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="batch-section">
                <h2><?= h($batch['name']) ?></h2>
                <div class="gallery-grid">
                    <?php foreach ($photos as $photo): ?>
                    <figure>
                        <img src="<?= h($photo['image_path']) ?>" alt="<?= h($photo['caption'] ?: $batch['name']) ?>">
                        <?php if ($photo['caption']): ?><figcaption><?= h($photo['caption']) ?></figcaption><?php endif; ?>
                    </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
