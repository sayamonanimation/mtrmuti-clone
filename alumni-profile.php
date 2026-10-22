<?php
$currentSlug = 'alumni-hub';
require_once __DIR__ . '/includes/header.php';

ensure_alumni_hub();
// Only approved profiles are public; the owner can preview their own.
$member = alumni_viewable_member($_GET['id'] ?? 0);
$works = $member ? get_alumni_works($member['id']) : [];
$skills = $member ? alumni_skill_list($member['skills']) : [];
?>
<div class="page-header">
    <div class="container">
        <h1><?= $member ? h($member['full_name']) : 'ไม่พบโปรไฟล์' ?></h1>
        <?php if ($member && $member['headline']): ?><p><?= h($member['headline']) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <p><a href="<?= h(clean_public_url('/alumni-hub')) ?>">&larr; กลับไปหน้าติดต่องานศิษย์เก่า</a></p>
        <?php if ($member): ?>
        <?php if ($member['status'] !== 'approved'): ?>
        <div class="hub-notice">👀 ตัวอย่างโปรไฟล์ — สถานะ: <strong><?= h(alumni_status_label($member['status'])) ?></strong> (ยังไม่แสดงต่อสาธารณะ)</div>
        <?php endif; ?>
        <div class="hub-profile">
            <aside class="hub-profile-side">
                <img class="hub-profile-photo" src="<?= h($member['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="<?= h($member['full_name']) ?>">
                <?php if ($member['available_for_work']): ?><div class="hub-badge hub-badge-static">● พร้อมรับงาน</div><?php endif; ?>
                <div class="hub-type-pill hub-type-<?= h($member['member_type'] ?? 'alumni') ?>"><?= h(alumni_member_type_label($member['member_type'] ?? 'alumni')) ?></div>
                <?php if (alumni_class_line($member, false) !== ''): ?><div class="hub-class"><?= h(alumni_class_line($member, false)) ?></div><?php endif; ?>
                <div class="hub-contact">
                    <?php if ($member['contact_email']): ?><a class="btn" href="mailto:<?= h($member['contact_email']) ?>">✉️ ส่งอีเมล</a><?php endif; ?>
                    <?php if ($member['contact_phone']): ?><a class="btn hero-fx-btn-ghost" href="tel:<?= h(preg_replace('/[^0-9+]/', '', $member['contact_phone'])) ?>">📞 <?= h($member['contact_phone']) ?></a><?php endif; ?>
                    <?php if ($member['line_id']): ?><a class="btn hero-fx-btn-ghost" href="https://line.me/ti/p/~<?= h(rawurlencode($member['line_id'])) ?>" target="_blank" rel="noopener">💬 LINE: <?= h($member['line_id']) ?></a><?php endif; ?>
                    <?php if ($member['website_url']): ?><a class="btn hero-fx-btn-ghost" href="<?= h($member['website_url']) ?>" target="_blank" rel="noopener">🌐 เว็บไซต์</a><?php endif; ?>
                    <a class="btn hero-fx-btn-ghost" href="<?= h(clean_public_url('/alumni-portfolio?id=' . (int) $member['id'])) ?>" target="_blank" rel="noopener">📄 Portfolio (PDF / E-book)</a>
                </div>
            </aside>
            <div class="hub-profile-main">
                <?php if ($skills): ?>
                <h2>ทักษะ</h2>
                <div class="hub-sphere" data-skills="<?= h(json_encode(array_values($skills), JSON_UNESCAPED_UNICODE)) ?>" aria-hidden="true"></div>
                <div class="hub-skills hub-skills-lg"><?php foreach ($skills as $s): ?><i><?= h($s) ?></i><?php endforeach; ?></div>
                <?php endif; ?>
                <?php if ($member['bio']): ?>
                <h2>เกี่ยวกับฉัน</h2>
                <div class="prose"><?= nl2br(h($member['bio'])) ?></div>
                <?php endif; ?>
                <h2>ผลงาน (<?= count($works) ?>)</h2>
                <?php if ($works): ?>
                <div class="hub-works-grid">
                    <?php foreach ($works as $i => $w): ?>
                    <figure class="hub-work">
                        <?php if ($w['image_path']): ?>
                        <button type="button" class="hub-work-img album-photo" data-src="<?= h($w['image_path']) ?>" data-caption="<?= h($w['title']) ?>"><img src="<?= h($w['image_path']) ?>" alt="<?= h($w['title']) ?>" loading="lazy"></button>
                        <?php endif; ?>
                        <?php if ($w['link_url']): ?>
                        <figcaption>
                            <a class="hub-work-link" href="<?= h($w['link_url']) ?>" target="_blank" rel="noopener" title="เปิดผลงาน: <?= h($w['title']) ?>">
                                <strong><?= h($w['title']) ?></strong>
                                <?php if ($w['description']): ?><span><?= nl2br(h($w['description'])) ?></span><?php endif; ?>
                                <em class="hub-work-go">ดูผลงาน <b aria-hidden="true">↗</b></em>
                            </a>
                        </figcaption>
                        <?php else: ?>
                        <figcaption>
                            <strong><?= h($w['title']) ?></strong>
                            <?php if ($w['description']): ?><span><?= nl2br(h($w['description'])) ?></span><?php endif; ?>
                        </figcaption>
                        <?php endif; ?>
                    </figure>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="hub-muted">ยังไม่มีผลงาน</p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <p>ไม่พบโปรไฟล์นี้ หรือยังไม่ได้รับการอนุมัติให้แสดง</p>
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
    var items = Array.prototype.slice.call(document.querySelectorAll('.hub-work-img'));
    if (!items.length) return;
    var img = box.querySelector('img'), cap = box.querySelector('figcaption'), counter = box.querySelector('.album-lightbox-counter'), index = 0;
    function show(i) { index = (i + items.length) % items.length; img.src = items[index].dataset.src; cap.textContent = items[index].dataset.caption || ''; counter.textContent = (index + 1) + ' / ' + items.length; }
    items.forEach(function (el, i) { el.addEventListener('click', function () { show(i); box.hidden = false; document.body.style.overflow = 'hidden'; }); });
    function close() { box.hidden = true; document.body.style.overflow = ''; }
    box.querySelector('.album-lightbox-close').onclick = close;
    box.querySelector('.prev').onclick = function () { show(index - 1); };
    box.querySelector('.next').onclick = function () { show(index + 1); };
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', function (e) { if (box.hidden) return; if (e.key === 'Escape') close(); if (e.key === 'ArrowLeft') show(index - 1); if (e.key === 'ArrowRight') show(index + 1); });
})();
</script>
<script src="/assets/js/alumni-fx.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/alumni-fx.js') ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
