<?php
$currentSlug = 'gallery';
require_once __DIR__ . '/includes/header.php';

$album = get_gallery_album((int) ($_GET['id'] ?? 0));
$photos = $album ? get_album_photos($album['id']) : [];
?>
<div class="page-header">
    <div class="container">
        <h1><?= $album ? h(lang_field($album, 'title')) : h(t('gallery.not_found')) ?></h1>
        <?php if ($album && ($album['event_date'] || $photos)): ?>
        <p><?= $album['event_date'] ? h(thai_date($album['event_date'])) . ' · ' : '' ?><?= count($photos) ?> <?= h(t('gallery.photos')) ?></p>
        <?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <p><a href="<?= h(clean_public_url('/gallery')) ?>">&larr; <?= h(t('gallery.all_albums')) ?></a></p>
        <?php if ($album && $album['description']): ?><div class="prose" style="margin-bottom:20px;"><?= nl2br(h($album['description'])) ?></div><?php endif; ?>
        <?php if ($photos): ?>
        <div class="album-photos">
            <?php foreach ($photos as $i => $p): ?>
            <button type="button" class="album-photo" data-index="<?= $i ?>" data-src="<?= h($p['image_path']) ?>" data-caption="<?= h($p['caption']) ?>" aria-label="<?= h(t('gallery.photos')) ?> <?= $i + 1 ?>">
                <img src="<?= h($p['image_path']) ?>" alt="<?= h($p['caption']) ?>" loading="lazy">
            </button>
            <?php endforeach; ?>
        </div>
        <?php elseif ($album): ?>
        <p><?= h(t('gallery.empty')) ?></p>
        <?php endif; ?>
    </div>
</div>
<div class="album-lightbox" id="albumLightbox" hidden>
    <button type="button" class="album-lightbox-close" aria-label="ปิด">&times;</button>
    <button type="button" class="album-lightbox-nav prev" aria-label="ก่อนหน้า">&lsaquo;</button>
    <figure><img src="" alt=""><figcaption></figcaption></figure>
    <button type="button" class="album-lightbox-nav next" aria-label="ถัดไป">&rsaquo;</button>
    <span class="album-lightbox-counter"></span>
</div>
<script>
(function () {
    var box = document.getElementById('albumLightbox');
    var items = Array.prototype.slice.call(document.querySelectorAll('.album-photo'));
    if (!box || !items.length) return;
    var img = box.querySelector('img'), cap = box.querySelector('figcaption'), counter = box.querySelector('.album-lightbox-counter');
    var index = 0, touchX = null;
    function show(i) {
        index = (i + items.length) % items.length;
        img.src = items[index].getAttribute('data-src');
        cap.textContent = items[index].getAttribute('data-caption') || '';
        counter.textContent = (index + 1) + ' / ' + items.length;
    }
    function open(i) { show(i); box.hidden = false; document.body.style.overflow = 'hidden'; }
    function close() { box.hidden = true; document.body.style.overflow = ''; }
    items.forEach(function (el, i) { el.addEventListener('click', function () { open(i); }); });
    box.querySelector('.album-lightbox-close').addEventListener('click', close);
    box.querySelector('.prev').addEventListener('click', function () { show(index - 1); });
    box.querySelector('.next').addEventListener('click', function () { show(index + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    box.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (e) {
        if (touchX === null) return;
        var dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) show(index + (dx < 0 ? 1 : -1));
        touchX = null;
    });
    document.addEventListener('keydown', function (e) {
        if (box.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') show(index - 1);
        if (e.key === 'ArrowRight') show(index + 1);
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
