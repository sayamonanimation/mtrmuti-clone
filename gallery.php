<?php
$currentSlug = 'gallery';
require_once __DIR__ . '/includes/header.php';

$albums = get_gallery_albums();
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(t('home.gallery_heading')) ?></h1>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <?php if ($albums): ?>
            <?php require __DIR__ . '/includes/album-grid.php'; ?>
        <?php else: ?>
            <p><?= h(t('gallery.empty')) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
