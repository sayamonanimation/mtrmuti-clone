<?php
$currentSlug = 'classroom';
require_once __DIR__ . '/includes/header.php';

$page = get_page('classroom');
$classrooms = get_classrooms();
$classroomPhotos = get_classroom_photos();
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

    <?php if ($classrooms): ?>
    <div class="container" style="margin-top:30px;">
        <div class="card-grid">
            <?php foreach ($classrooms as $c): ?>
            <?php $list = $classroomPhotos[$c['id']] ?? []; ?>
            <div class="card classroom-card">
                <?php if ($list): ?>
                <div class="classroom-gallery">
                    <button type="button" class="classroom-main" aria-label="ดูรูปขนาดใหญ่">
                        <img src="<?= h($list[0]['image_path']) ?>" alt="<?= h(lang_field($c, 'name')) ?>">
                    </button>
                    <?php if (count($list) > 1): ?>
                    <div class="classroom-thumbs">
                        <?php foreach ($list as $i => $photo): ?>
                        <button type="button" class="classroom-thumb<?= $i === 0 ? ' is-active' : '' ?>" data-src="<?= h($photo['image_path']) ?>" aria-label="รูปที่ <?= $i + 1 ?>">
                            <img src="<?= h($photo['image_path']) ?>" alt="" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h3><?= h(lang_field($c, 'name')) ?></h3>
                    <?php if (lang_field($c, 'description')): ?><p><?= h(lang_field($c, 'description')) ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<div class="classroom-lightbox" id="classroomLightbox" hidden>
    <button type="button" class="classroom-lightbox-close" aria-label="ปิด">&times;</button>
    <button type="button" class="classroom-lightbox-nav prev" aria-label="รูปก่อนหน้า">&lsaquo;</button>
    <img src="" alt="">
    <button type="button" class="classroom-lightbox-nav next" aria-label="รูปถัดไป">&rsaquo;</button>
</div>
<script>
(function () {
    var box = document.getElementById('classroomLightbox');
    var boxImg = box.querySelector('img');
    var list = [], index = 0;

    function show(i) {
        index = (i + list.length) % list.length;
        boxImg.src = list[index];
    }
    function open(srcs, i) {
        list = srcs;
        show(i);
        box.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function close() {
        box.hidden = true;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.classroom-gallery').forEach(function (gallery) {
        var mainImg = gallery.querySelector('.classroom-main img');
        var thumbs = gallery.querySelectorAll('.classroom-thumb');
        var srcs = thumbs.length ? Array.prototype.map.call(thumbs, function (t) { return t.getAttribute('data-src'); }) : [mainImg.getAttribute('src')];
        var current = 0;
        thumbs.forEach(function (thumb, i) {
            thumb.addEventListener('click', function () {
                current = i;
                mainImg.src = srcs[i];
                thumbs.forEach(function (t) { t.classList.toggle('is-active', t === thumb); });
            });
        });
        gallery.querySelector('.classroom-main').addEventListener('click', function () { open(srcs, current); });
    });

    box.querySelector('.classroom-lightbox-close').addEventListener('click', close);
    box.querySelector('.prev').addEventListener('click', function () { show(index - 1); });
    box.querySelector('.next').addEventListener('click', function () { show(index + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', function (e) {
        if (box.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') show(index - 1);
        if (e.key === 'ArrowRight') show(index + 1);
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
