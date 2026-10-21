<?php
$currentSlug = 'outstanding-alumni';
require_once __DIR__ . '/includes/header.php';

ensure_outstanding_alumni();
$page = get_page('outstanding-alumni');
$people = get_outstanding_alumni();
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title') ?: t('alumni.outstanding')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <div class="alumni-tabs">
            <a href="<?= h(clean_public_url('/alumni')) ?>"><?= h(t('alumni.all')) ?></a>
            <a class="active" href="<?= h(clean_public_url('/outstanding-alumni')) ?>">🏆 <?= h(t('alumni.outstanding')) ?></a>
        </div>
        <?php if (trim(strip_tags((string) lang_field($page, 'content'))) !== ''): ?>
        <div class="prose" style="margin-bottom:24px;"><?= lang_field($page, 'content') ?></div>
        <?php endif; ?>

        <?php if ($people): ?>
        <div class="oa-grid">
            <?php foreach ($people as $p): ?>
            <article class="oa-card">
                <div class="oa-photo">
                    <img src="<?= h($p['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="<?= h($p['name']) ?>" loading="lazy">
                    <span class="oa-ribbon">🏆 <?= h(t('alumni.outstanding')) ?></span>
                </div>
                <div class="oa-body">
                    <h3><?= h($p['name']) ?></h3>
                    <?php if ($p['batch_name'] || $p['graduation_year']): ?>
                    <div class="oa-class"><?= h(trim($p['batch_name'] . ($p['graduation_year'] ? ' · ' . t('alumni.graduated') . ' ' . $p['graduation_year'] : ''), ' ·')) ?></div>
                    <?php endif; ?>
                    <?php if ($p['position'] || $p['workplace']): ?>
                    <div class="oa-job">💼 <?= h($p['position']) ?><?= $p['position'] && $p['workplace'] ? ' — ' : '' ?><?= h($p['workplace']) ?></div>
                    <?php endif; ?>
                    <?php if ($p['achievement']): ?><div class="oa-achievement"><?= render_text_list($p['achievement']) ?></div><?php endif; ?>
                    <?php if ($p['quote']): ?><blockquote class="oa-quote">“<?= h($p['quote']) ?>”</blockquote><?php endif; ?>
                    <?php if ($p['link_url']): ?><a class="oa-link" href="<?= h($p['link_url']) ?>" target="_blank" rel="noopener"><?= h(t('alumni.more')) ?> ↗</a><?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p><?= h(t('alumni.outstanding_empty')) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
