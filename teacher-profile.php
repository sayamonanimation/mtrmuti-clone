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

$sections = [
    'education' => t('profile.education'),
    'subjects_taught' => t('profile.subjects_taught'),
    'research_works' => t('profile.research_works'),
    'creative_works' => t('profile.creative_works'),
    'innovation_works' => t('profile.innovation_works'),
    'competition_works' => t('profile.competition_works'),
    'intellectual_property' => t('profile.intellectual_property'),
    'copyrights_patents' => t('profile.copyrights_patents'),
    'awards' => t('profile.awards'),
];

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
                <img src="<?= h($teacher['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="<?= h($teacher['name']) ?>">
                <?php if ($teacher['phone'] || $teacher['email']): ?>
                <div class="teacher-contact teacher-contact-profile">
                    <?php if ($teacher['phone']): ?><span><?= h(t('teachers.phone_prefix')) ?>: <?= h($teacher['phone']) ?></span><?php endif; ?>
                    <?php if ($teacher['email']): ?><span><a href="mailto:<?= h($teacher['email']) ?>"><?= h($teacher['email']) ?></a></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($teacher['classroom_online_url']): ?>
                <a class="btn" style="display:block; text-align:center; margin-top:14px;" href="<?= h($teacher['classroom_online_url']) ?>" target="_blank" rel="noopener"><?= h(t('teachers.classroom_online')) ?></a>
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
                        $sectionText = lang_field($teacher, $column);
                        if (empty($sectionText) && empty($images)) {
                            continue;
                        }
                        ?>
                        <div class="profile-section">
                            <h2><?= h($label) ?></h2>
                            <?php if (!empty($sectionText)): ?>
                                <?= render_text_list($sectionText) ?>
                            <?php endif; ?>
                            <?php if ($images): ?>
                            <div class="portfolio-thumbs">
                                <?php foreach ($images as $img): ?>
                                <figure>
                                    <a href="<?= h($img['image_path']) ?>" target="_blank" rel="noopener">
                                        <img src="<?= h($img['image_path']) ?>" alt="<?= h($img['caption'] ?: $label) ?>">
                                    </a>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
