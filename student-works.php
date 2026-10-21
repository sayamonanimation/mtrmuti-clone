<?php
$currentSlug = 'student-works';
require_once __DIR__ . '/includes/header.php';

$batches = get_student_work_batches();
$worksByBatch = [];
foreach ($batches as $batch) {
    $worksByBatch[$batch['id']] = get_student_works($batch['id']);
}
$media = get_all_student_work_media();
$workData = [];
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
                    <?php
                    $photos = array_column($media['photos'][$w['id']] ?? [], 'image_path') ?: array_filter([$w['image_path']]);
                    $links = $media['links'][$w['id']] ?? [];
                    $index = count($workData);
                    $workData[] = [
                        'title' => lang_field($w, 'title'),
                        'description' => lang_field($w, 'description'),
                        'author' => (string) $w['student_name'],
                        'photos' => array_values($photos),
                        'links' => array_map(fn($l) => ['label' => $l['label'] ?: preg_replace('#^https?://(www\.)?([^/]+).*$#i', '$2', $l['url']), 'url' => $l['url']], $links),
                    ];
                    ?>
                    <div class="card card-link work-card" role="button" tabindex="0" data-work="<?= $index ?>" aria-label="<?= h(lang_field($w, 'title')) ?>">
                        <div class="work-card-media">
                            <img src="<?= h(reset($photos) ?: '') ?>" alt="<?= h(lang_field($w, 'title')) ?>" loading="lazy">
                            <?php if (count($photos) > 1 || $links): ?>
                            <span class="work-card-badges">
                                <?php if (count($photos) > 1): ?><span>📷 <?= count($photos) ?></span><?php endif; ?>
                                <?php if ($links): ?><span>🔗 <?= count($links) ?></span><?php endif; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?= h(lang_field($w, 'title')) ?></h3>
                            <?php if (lang_field($w, 'description')): ?><p><?= h(lang_field($w, 'description')) ?></p><?php endif; ?>
                            <?php if ($w['student_name']): ?><p class="student-work-author"><?= h($w['student_name']) ?></p><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="container"><p><?= h(t('student_works.no_works')) ?></p></div>
    <?php endif; ?>
</div>
<div class="work-modal" id="workModal" hidden role="dialog" aria-modal="true" aria-labelledby="workModalTitle">
    <div class="work-modal-box">
        <button type="button" class="work-modal-close" aria-label="ปิด">&times;</button>
        <div class="work-modal-stage">
            <img src="" alt="">
            <button type="button" class="work-modal-nav prev" aria-label="ก่อนหน้า">&lsaquo;</button>
            <button type="button" class="work-modal-nav next" aria-label="ถัดไป">&rsaquo;</button>
            <span class="work-modal-counter"></span>
        </div>
        <div class="work-modal-thumbs"></div>
        <div class="work-modal-info">
            <h3 id="workModalTitle"></h3>
            <p class="student-work-author"></p>
            <p class="work-modal-desc"></p>
            <div class="work-modal-links"></div>
        </div>
    </div>
</div>
<script>
(function () {
    var works = <?= json_encode($workData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var modal = document.getElementById('workModal');
    if (!modal || !works.length) return;
    var img = modal.querySelector('.work-modal-stage img'), counter = modal.querySelector('.work-modal-counter');
    var thumbs = modal.querySelector('.work-modal-thumbs'), linksBox = modal.querySelector('.work-modal-links');
    var prev = modal.querySelector('.prev'), next = modal.querySelector('.next');
    var work = null, index = 0, touchX = null, lastFocus = null;

    function show(i) {
        index = (i + work.photos.length) % work.photos.length;
        img.src = work.photos[index];
        img.alt = work.title + ' ' + (index + 1);
        counter.textContent = work.photos.length > 1 ? (index + 1) + ' / ' + work.photos.length : '';
        Array.prototype.forEach.call(thumbs.children, function (t, n) { t.classList.toggle('is-active', n === index); });
    }
    function open(n) {
        work = works[n];
        lastFocus = document.activeElement;
        modal.querySelector('h3').textContent = work.title;
        modal.querySelector('.student-work-author').textContent = work.author;
        modal.querySelector('.work-modal-desc').textContent = work.description;
        var many = work.photos.length > 1;
        prev.hidden = next.hidden = !many;
        thumbs.innerHTML = '';
        thumbs.hidden = !many;
        work.photos.forEach(function (src, i) {
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = '<img alt="" loading="lazy">';
            b.firstChild.src = src;
            b.addEventListener('click', function () { show(i); });
            thumbs.appendChild(b);
        });
        linksBox.innerHTML = '';
        work.links.forEach(function (l) {
            var a = document.createElement('a');
            a.className = 'btn';
            a.href = l.url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.textContent = '🔗 ' + l.label;
            linksBox.appendChild(a);
        });
        show(0);
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('.work-modal-close').focus();
    }
    function close() {
        modal.hidden = true;
        document.body.style.overflow = '';
        if (lastFocus) lastFocus.focus();
    }
    document.querySelectorAll('.work-card').forEach(function (card) {
        card.addEventListener('click', function () { open(parseInt(card.getAttribute('data-work'), 10)); });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        });
    });
    modal.querySelector('.work-modal-close').addEventListener('click', close);
    prev.addEventListener('click', function () { show(index - 1); });
    next.addEventListener('click', function () { show(index + 1); });
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    var stage = modal.querySelector('.work-modal-stage');
    stage.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
        if (touchX === null || work.photos.length < 2) return;
        var dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) show(index + (dx < 0 ? 1 : -1));
        touchX = null;
    });
    document.addEventListener('keydown', function (e) {
        if (modal.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft' && work.photos.length > 1) show(index - 1);
        if (e.key === 'ArrowRight' && work.photos.length > 1) show(index + 1);
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
