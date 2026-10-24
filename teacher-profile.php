<?php
$currentSlug = 'teachers';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$teacher = $id ? get_teacher($id) : null;

if (!$teacher) {
    http_response_code(404);
    ?>
    <div class="page-header"><div class="container"><h1><?= h(t('teachers.not_found')) ?></h1></div></div>
    <div class="page-content"><div class="container"><p><a href="<?= h(clean_public_url('/teachers')) ?>">&larr; <?= h(t('teachers.back_to_list')) ?></a></p></div></div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

ensure_teacher_works_levels();
$sections = [
    'education' => t('profile.education'),
    'subjects_taught' => t('profile.subjects_taught'),
    'research_works' => t('profile.research_works'),
    'creative_works' => t('profile.creative_works'),
    'innovation_works' => t('profile.innovation_works'),
    'competition_works' => t('profile.competition_works'),
    'copyrights_patents' => t('profile.copyrights_patents'),
    'awards' => t('profile.awards'),
];

// Works sections are Thai only; research / creative works come in two levels.
$thaiOnly = ['research_works', 'creative_works', 'innovation_works', 'competition_works', 'intellectual_property', 'copyrights_patents', 'awards'];
$classroomLinks = get_teacher_classroom_links($teacher['id']);
$portfolioCategories = portfolio_categories();
$portfolioImages = array_fill_keys(array_keys($portfolioCategories), []);
foreach (get_teacher_portfolio($teacher['id']) as $item) {
    $portfolioImages[$item['category']][] = $item;
}
?>
<div class="page-header">
    <div class="container">
        <h1><?= h($teacher['name']) ?></h1>
        <?php if (lang_field($teacher, 'position')): ?><p><?= h(lang_field($teacher, 'position')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <p><a href="<?= h(clean_public_url('/teachers')) ?>">&larr; <?= h(t('teachers.back_to_list_all')) ?></a></p>
        <div class="teacher-profile">
            <div class="teacher-profile-photo">
                <?php $identity = teacher_identity($teacher); ?>
                <div class="tid tid-large" data-themes="<?= h(implode(',', $identity['themes'])) ?>" data-tags="<?= h(json_encode($identity['tags'], JSON_UNESCAPED_UNICODE)) ?>">
                    <img src="<?= h($teacher['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="<?= h($teacher['name']) ?>" crossorigin="anonymous">
                </div>
                <?php $publicPhone = teacher_public_phone($teacher); ?>
                <?php if ($publicPhone || $teacher['email']): ?>
                <div class="teacher-contact teacher-contact-profile">
                    <?php if ($publicPhone): ?><span><?= h(t('teachers.phone_prefix')) ?>: <?= h($publicPhone) ?></span><?php endif; ?>
                    <?php if ($teacher['email']): ?><span><a href="mailto:<?= h($teacher['email']) ?>"><?= h($teacher['email']) ?></a></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($classroomLinks): ?>
                <div class="classroom-btns">
                    <?php foreach ($classroomLinks as $cl): ?>
                    <a class="btn" href="<?= h($cl['url']) ?>" target="_blank" rel="noopener">🎓 <?= h($cl['label'] ?: t('teachers.classroom_online')) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="teacher-profile-body">
                <?php if (lang_field($teacher, 'bio')): ?>
                <div class="profile-section profile-section-bio">
                    <h2><?= h(t('profile.bio')) ?></h2>
                    <?= render_text_list(lang_field($teacher, 'bio')) ?>
                </div>
                <?php endif; ?>

                <div class="profile-grid">
                    <?php foreach ($sections as $column => $label): ?>
                        <?php
                        $images = $portfolioImages[$column] ?? [];
                        $sectionText = in_array($column, $thaiOnly, true) ? (string) ($teacher[$column] ?? '') : (string) lang_field($teacher, $column);
                        $intlText = in_array($column, TEACHER_LEVELLED_WORKS, true) ? (string) ($teacher[$column . '_intl'] ?? '') : '';
                        if (trim($sectionText) === '' && trim($intlText) === '' && empty($images)) {
                            continue;
                        }
                        ?>
                        <div class="profile-section">
                            <h2><?= h($label) ?></h2>
                            <?php if (in_array($column, TEACHER_LEVELLED_WORKS, true)): ?>
                                <?php if (trim($intlText) !== ''): ?><h3 class="level-head"><span class="level-tag level-tag-intl">ระดับนานาชาติ</span></h3><?= render_text_list($intlText) ?><?php endif; ?>
                                <?php if (trim($sectionText) !== ''): ?><h3 class="level-head"><span class="level-tag level-tag-th">ระดับชาติ</span></h3><?= render_text_list($sectionText) ?><?php endif; ?>
                            <?php elseif (trim($sectionText) !== ''): ?>
                                <?= render_text_list($sectionText) ?>
                            <?php endif; ?>
                            <?php if ($images): ?>
                            <div class="portfolio-thumbs">
                                <?php foreach ($images as $img): ?>
                                <figure>
                                    <button type="button" class="pv-thumb" data-src="<?= h($img['image_path']) ?>" data-caption="<?= h($img['caption']) ?>" aria-label="ดูภาพ <?= h($img['caption'] ?: $label) ?>">
                                        <img src="<?= h($img['image_path']) ?>" alt="<?= h($img['caption'] ?: $label) ?>" draggable="false">
                                    </button>
                                    <?php if ($img['caption']): ?><figcaption><?= h($img['caption']) ?></figcaption><?php endif; ?>
                                </figure>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Works open in a viewer only: no link to the file, no right-click / drag saving. -->
<div class="album-lightbox pv-box" id="pvBox" hidden>
    <button type="button" class="album-lightbox-close" aria-label="ปิด">&times;</button>
    <button type="button" class="album-lightbox-nav prev" aria-label="ก่อนหน้า">&lsaquo;</button>
    <figure><img src="" alt="" draggable="false"><figcaption></figcaption></figure>
    <button type="button" class="album-lightbox-nav next" aria-label="ถัดไป">&rsaquo;</button>
    <span class="album-lightbox-counter"></span>
</div>
<script>
(function () {
    var box = document.getElementById('pvBox');
    var items = Array.prototype.slice.call(document.querySelectorAll('.pv-thumb'));
    document.querySelectorAll('.portfolio-thumbs, #pvBox').forEach(function (el) {
        el.addEventListener('contextmenu', function (e) { if (e.target.tagName === 'IMG') e.preventDefault(); });
        el.addEventListener('dragstart', function (e) { if (e.target.tagName === 'IMG') e.preventDefault(); });
    });
    if (!items.length) return;
    var img = box.querySelector('img'), cap = box.querySelector('figcaption'), counter = box.querySelector('.album-lightbox-counter'), index = 0;
    function show(i) { index = (i + items.length) % items.length; img.src = items[index].dataset.src; cap.textContent = items[index].dataset.caption || ''; counter.textContent = items.length > 1 ? (index + 1) + ' / ' + items.length : ''; }
    function close() { box.hidden = true; document.body.style.overflow = ''; }
    items.forEach(function (el, i) { el.addEventListener('click', function () { show(i); box.hidden = false; document.body.style.overflow = 'hidden'; }); });
    box.querySelector('.album-lightbox-close').onclick = close;
    box.querySelector('.prev').onclick = function () { show(index - 1); };
    box.querySelector('.next').onclick = function () { show(index + 1); };
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', function (e) { if (box.hidden) return; if (e.key === 'Escape') close(); if (e.key === 'ArrowLeft') show(index - 1); if (e.key === 'ArrowRight') show(index + 1); });
})();
</script>
<script src="/assets/js/teacher-fx.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/teacher-fx.js') ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
