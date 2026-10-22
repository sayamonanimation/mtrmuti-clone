<?php
/**
 * A hub member's portfolio as a print-ready A4 document: profile, skills,
 * their own portfolio sections and works. "บันทึกเป็น PDF" uses the
 * browser's print dialog (keeps Thai text sharp and selectable, and images
 * from any domain); ?print=1 opens that dialog straight away. The same
 * content is offered as an EPUB e-book by alumni-portfolio-epub.php.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();

$member = alumni_viewable_member($_GET['id'] ?? 0);
if (!$member) {
    http_response_code(404);
}
$items = $member ? get_alumni_portfolio_items($member['id']) : [];
$works = $member ? get_alumni_works($member['id']) : [];
$skills = $member ? alumni_skill_list($member['skills']) : [];
$siteName = get_site_name();
$profileUrl = $member ? alumni_absolute_url('/alumni-profile?id=' . (int) $member['id']) : '';
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $member ? 'Portfolio - ' . h($member['full_name']) : 'ไม่พบ Portfolio' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/portfolio.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/portfolio.css') ?>">
</head>
<body>
<?php if (!$member): ?>
<main class="pf-page"><h1>ไม่พบ Portfolio</h1><p>โปรไฟล์นี้ไม่มีอยู่ หรือยังไม่ได้รับการอนุมัติให้แสดง</p></main>
<?php else: ?>
<div class="pf-toolbar">
    <a href="<?= h(clean_public_url('/alumni-profile?id=' . (int) $member['id'])) ?>">&larr; กลับไปโปรไฟล์</a>
    <span class="pf-toolbar-actions">
        <button type="button" onclick="window.print()">⬇️ บันทึกเป็น PDF</button>
        <a class="pf-btn" href="<?= h(clean_public_url('/alumni-portfolio-epub?id=' . (int) $member['id'])) ?>">📚 ดาวน์โหลด E-book (EPUB)</a>
    </span>
    <small>บันทึกเป็น PDF: ในหน้าต่างพิมพ์ เลือก "บันทึกเป็น PDF / Save as PDF" แล้วกดบันทึก</small>
</div>

<main class="pf-page">
    <header class="pf-cover">
        <img class="pf-photo" src="<?= h($member['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="">
        <div>
            <p class="pf-kicker">PORTFOLIO</p>
            <h1><?= h($member['full_name']) ?></h1>
            <?php if ($member['headline']): ?><p class="pf-headline"><?= h($member['headline']) ?></p><?php endif; ?>
            <p class="pf-meta"><?= h(alumni_member_type_label($member['member_type'] ?? 'alumni')) ?><?= alumni_class_line($member, false) !== '' ? ' · ' . h(alumni_class_line($member, false)) : '' ?></p>
            <ul class="pf-contact">
                <?php if ($member['contact_email']): ?><li>✉️ <?= h($member['contact_email']) ?></li><?php endif; ?>
                <?php if ($member['contact_phone']): ?><li>📞 <?= h($member['contact_phone']) ?></li><?php endif; ?>
                <?php if ($member['line_id']): ?><li>💬 LINE: <?= h($member['line_id']) ?></li><?php endif; ?>
                <?php if ($member['website_url']): ?><li>🌐 <a href="<?= h($member['website_url']) ?>"><?= h(preg_replace('#^https?://#', '', $member['website_url'])) ?></a></li><?php endif; ?>
            </ul>
        </div>
    </header>

    <?php if ($member['bio']): ?>
    <section class="pf-section">
        <h2>เกี่ยวกับฉัน</h2>
        <p class="pf-text"><?= nl2br(h($member['bio'])) ?></p>
    </section>
    <?php endif; ?>

    <?php if ($skills): ?>
    <section class="pf-section">
        <h2>ทักษะ</h2>
        <div class="pf-skills"><?php foreach ($skills as $s): ?><span><?= h($s) ?></span><?php endforeach; ?></div>
    </section>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
    <section class="pf-section pf-item<?= $it['image_path'] ? ' has-image' : '' ?>">
        <h2><?= h($it['heading']) ?></h2>
        <div class="pf-item-body">
            <?php if ($it['image_path']): ?><img src="<?= h($it['image_path']) ?>" alt=""><?php endif; ?>
            <div>
                <?php if ($it['body']): ?><p class="pf-text"><?= nl2br(h($it['body'])) ?></p><?php endif; ?>
                <?php if ($it['link_url']): ?><p class="pf-link">🔗 <a href="<?= h($it['link_url']) ?>"><?= h($it['link_label'] ?: $it['link_url']) ?></a><?php if ($it['link_label']): ?> <small><?= h($it['link_url']) ?></small><?php endif; ?></p><?php endif; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if ($works): ?>
    <section class="pf-section">
        <h2>ผลงาน</h2>
        <div class="pf-works">
            <?php foreach ($works as $w): ?>
            <article class="pf-work">
                <?php if ($w['image_path']): ?><img src="<?= h($w['image_path']) ?>" alt=""><?php endif; ?>
                <h3><?= h($w['title']) ?></h3>
                <?php if ($w['description']): ?><p><?= nl2br(h($w['description'])) ?></p><?php endif; ?>
                <?php if ($w['link_url']): ?><p class="pf-link">🔗 <a href="<?= h($w['link_url']) ?>"><?= h($w['link_url']) ?></a></p><?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <footer class="pf-footer">
        <span><?= h($siteName) ?> · ติดต่องานศิษย์เก่า</span>
        <span><?= h($profileUrl) ?></span>
    </footer>
</main>
<?php if (!empty($_GET['print'])): ?>
<script>
// Wait for photos so they are in the PDF, then open the print dialog.
window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
</script>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
