<?php
$currentSlug = 'course';
require_once __DIR__ . '/includes/header.php';

$page = get_page('course');
$curriculumPdf = get_setting('curriculum_pdf_path');
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <div class="content-card prose">
            <?= lang_field($page, 'content') ?>
        </div>

        <?php if ($curriculumPdf): ?>
        <div class="curriculum-download-cta">
            <a class="btn" href="<?= h($curriculumPdf) ?>" download="เล่มหลักสูตร-<?= h($page['title']) ?>.pdf" target="_blank" rel="noopener">
                <?= h(t('course.download_curriculum')) ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
