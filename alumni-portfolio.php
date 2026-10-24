<?php
/**
 * A hub member's portfolio as a print-ready A4 document: profile, skills,
 * their own portfolio sections and works. "บันทึกเป็น PDF" uses the
 * browser's print dialog (keeps Thai text sharp and selectable, and images
 * from any domain); ?print=1 opens that dialog straight away.
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
// Owner's chosen look; the owner (or an admin) can preview others with ?theme=.
$themeKey = $member ? alumni_portfolio_theme($member['portfolio_theme'] ?? 'red') : 'red';
if ($member && isset($_GET['theme']) && (alumni_member_id() === (int) $member['id'] || is_admin_logged_in())) {
    $themeKey = alumni_portfolio_theme($_GET['theme']);
}
$theme = alumni_portfolio_themes()[$themeKey];
$layoutKey = $member ? alumni_portfolio_layout($member['portfolio_layout'] ?? 'classic') : 'classic';
if ($member && isset($_GET['layout']) && (alumni_member_id() === (int) $member['id'] || is_admin_logged_in())) {
    $layoutKey = alumni_portfolio_layout($_GET['layout']);
}
$handle = $member ? strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($member['contact_email'] ? strstr($member['contact_email'], '@', true) : 'member-' . $member['id']))) : '';
$sectionNo = 0;
$num = function () use (&$sectionNo) { return sprintf('%02d', ++$sectionNo); };
?><!DOCTYPE html>
<html lang="th" class="pf-theme-<?= h($themeKey) ?> pf-layout-<?= h($layoutKey) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $member ? 'Portfolio - ' . h($member['full_name']) : 'ไม่พบ Portfolio' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@400;500;600&family=JetBrains+Mono:wght@400;600&family=Orbitron:wght@600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/portfolio.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/portfolio.css') ?>">
</head>
<body>
<?php if (!$member): ?>
<main class="pf-page pf-empty"><h1>ไม่พบ Portfolio</h1><p>โปรไฟล์นี้ไม่มีอยู่ หรือยังไม่ได้รับการอนุมัติให้แสดง</p></main>
<?php else: ?>
<div class="pf-bg" aria-hidden="true"><i class="pf-grid"></i><i class="pf-orb pf-orb-a"></i><i class="pf-orb pf-orb-b"></i><i class="pf-scan"></i></div>
<div class="pf-watermark" aria-hidden="true"><span>MULTIMEDIA<br>TECHNOLOGY</span></div>

<div class="pf-toolbar">
    <a href="<?= h(clean_public_url('/alumni-profile?id=' . (int) $member['id'])) ?>">&larr; กลับไปโปรไฟล์</a>
    <span class="pf-toolbar-actions">
        <button type="button" onclick="window.print()">⬇️ บันทึกเป็น PDF</button>
    </span>
    <small>บันทึกเป็น PDF: ในหน้าต่างพิมพ์ เลือก "บันทึกเป็น PDF / Save as PDF" และเปิด "กราฟิกพื้นหลัง / Background graphics"</small>
</div>

<main class="pf-page">
    <header class="pf-cover pf-hud">
        <!-- Layout decorations: each shows only in its own layout (portfolio.css). -->
        <div class="pf-deco pf-deco-term" aria-hidden="true"><i></i><i></i><i></i><span>~/portfolio/<?= h($handle) ?> — zsh</span></div>
        <div class="pf-deco pf-deco-slate" aria-hidden="true">
            <div class="pf-slate-stripes"></div>
            <dl>
                <div><dt>PROD.</dt><dd>PORTFOLIO</dd></div>
                <div><dt>DIRECTOR</dt><dd><?= h($member['full_name']) ?></dd></div>
                <div><dt>ROLL</dt><dd>MT-<?= sprintf('%04d', (int) $member['id']) ?></dd></div>
                <div><dt>DATE</dt><dd><?= date('d/m/') . (date('Y') + 543) ?></dd></div>
            </dl>
        </div>
        <div class="pf-cover-top">
            <span class="pf-mono">// DIGITAL PORTFOLIO <span class="pf-deco pf-deco-rec">● REC</span></span>
            <span class="pf-mono"><span class="pf-deco pf-deco-tc" id="pfTimecode">00:00:00:00</span> MT-<?= sprintf('%04d', (int) $member['id']) ?> · <?= date('Y') + 543 ?></span>
        </div>
        <div class="pf-cover-main">
            <div class="pf-avatar">
                <i class="pf-ring" aria-hidden="true"></i>
                <img src="<?= h($member['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="">
            </div>
            <div class="pf-ident">
                <p class="pf-kicker">PORTFOLIO</p>
                <h1><?= h($member['full_name']) ?></h1>
                <?php if (alumni_nickname_text($member) !== ''): ?><p class="pf-nick"><?= h(alumni_nickname_text($member)) ?></p><?php endif; ?>
                <?php if ($member['headline']): ?><p class="pf-headline"><?= h($member['headline']) ?></p><?php endif; ?>
                <p class="pf-meta"><span class="pf-tag"><?= h(alumni_member_type_label($member['member_type'] ?? 'alumni')) ?></span><?php if (alumni_class_line($member, false) !== ''): ?><span><?= h(alumni_class_line($member, false)) ?></span><?php endif; ?><?php if ($member['available_for_work']): ?><span class="pf-live">● OPEN FOR WORK</span><?php endif; ?></p>
            </div>
            <div class="pf-qr-box">
                <div class="pf-qr" data-url="<?= h($profileUrl) ?>"></div>
                <span class="pf-mono">SCAN · ONLINE PROFILE</span>
            </div>
        </div>
        <ul class="pf-contact">
            <?php if ($member['contact_email']): ?><li><b>EMAIL</b><?= h($member['contact_email']) ?></li><?php endif; ?>
            <?php if ($member['contact_phone']): ?><li><b>TEL</b><?= h($member['contact_phone']) ?></li><?php endif; ?>
            <?php if ($member['line_id']): ?><li><b>LINE</b><?= h($member['line_id']) ?></li><?php endif; ?>
            <?php if ($member['website_url']): ?><li><b>WEB</b><a href="<?= h($member['website_url']) ?>"><?= h(preg_replace('#^https?://#', '', $member['website_url'])) ?></a></li><?php endif; ?>
        </ul>
    </header>

    <?php if ($member['bio'] || $skills): ?>
    <div class="pf-duo pf-block-about">
        <?php if ($member['bio']): ?>
        <section class="pf-section pf-panel" data-file="about.md">
            <h2><em><?= $num() ?></em>เกี่ยวกับฉัน <small>ABOUT</small></h2>
            <p class="pf-text"><?= nl2br(h($member['bio'])) ?></p>
        </section>
        <?php endif; ?>
        <?php if ($skills): ?>
        <section class="pf-section pf-panel" data-file="skills.json">
            <h2><em><?= $num() ?></em>ทักษะ <small>SKILLS</small></h2>
            <div class="pf-skills"><?php foreach ($skills as $i => $s): ?><span style="--i:<?= $i ?>"><?= h($s) ?></span><?php endforeach; ?></div>
        </section>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
    <section class="pf-section pf-panel pf-item pf-block-item<?= $it['image_path'] ? ' has-image' : '' ?>" data-file="section-<?= sprintf('%02d', $sectionNo + 1) ?>.md">
        <h2><em><?= $num() ?></em><?= h($it['heading']) ?></h2>
        <div class="pf-item-body">
            <?php if ($it['image_path']): ?><figure class="pf-frame"><img src="<?= h($it['image_path']) ?>" alt=""></figure><?php endif; ?>
            <div>
                <?php if ($it['body']): ?><p class="pf-text"><?= nl2br(h($it['body'])) ?></p><?php endif; ?>
                <?php if ($it['link_url']): ?><a class="pf-link" href="<?= h($it['link_url']) ?>"><span><?= h($it['link_label'] ?: 'เปิดลิงก์') ?> ↗</span><small><?= h($it['link_url']) ?></small></a><?php endif; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if ($works): ?>
    <section class="pf-section pf-block-works" data-file="works/">
        <h2><em><?= $num() ?></em>ผลงาน <small>SELECTED WORKS · <?= count($works) ?></small></h2>
        <div class="pf-works">
            <?php foreach ($works as $i => $w): ?>
            <article class="pf-work">
                <?php if ($w['image_path']): ?><figure class="pf-frame"><span class="pf-deco pf-browser" aria-hidden="true"><i></i><i></i><i></i><b><?= h($w['link_url'] ? preg_replace('#^https?://(www\.)?#', '', $w['link_url']) : 'localhost:3000') ?></b></span><img src="<?= h($w['image_path']) ?>" alt=""><span class="pf-work-no"><?= sprintf('%02d', $i + 1) ?></span></figure><?php endif; ?>
                <div class="pf-work-body">
                    <h3><?= h($w['title']) ?></h3>
                    <?php if ($w['description']): ?><p><?= nl2br(h($w['description'])) ?></p><?php endif; ?>
                    <?php if ($w['link_url']): ?><a class="pf-link" href="<?= h($w['link_url']) ?>"><span>ดูผลงาน ↗</span><small><?= h($w['link_url']) ?></small></a><?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <footer class="pf-footer">
        <span class="pf-mono">MULTIMEDIA TECHNOLOGY · <?= h($siteName) ?></span>
        <span class="pf-mono"><?= h($profileUrl) ?></span>
    </footer>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    // QR code to the online profile (scannable from the printed PDF too).
    var box = document.querySelector('.pf-qr');
    // Cinema layout: a running timecode in the cover (static when printed).
    var tc = document.getElementById('pfTimecode');
    if (tc && document.documentElement.classList.contains('pf-layout-cinema') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var t0 = performance.now();
        (function tick(now) {
            var f = Math.floor((now - t0) / (1000 / 24)), p = function (n) { return ('0' + n).slice(-2); };
            tc.textContent = p(Math.floor(f / 86400) % 24) + ':' + p(Math.floor(f / 1440) % 60) + ':' + p(Math.floor(f / 24) % 60) + ':' + p(f % 24);
            requestAnimationFrame(tick);
        })(t0);
    }
    if (box && window.QRCode) {
        new QRCode(box, { text: box.getAttribute('data-url'), width: 96, height: 96, colorDark: <?= json_encode($theme['qr']) ?>, colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
    } else if (box) {
        box.parentNode.hidden = true;
    }
})();
</script>
<?php if (!empty($_GET['print'])): ?>
<script>
// Wait for photos so they are in the PDF, then open the print dialog.
window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
</script>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
