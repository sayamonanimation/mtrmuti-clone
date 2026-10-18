<?php
$currentSlug = 'teachers';
require_once __DIR__ . '/includes/header.php';

$page = get_page('teachers');
$teachers = get_teachers();
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <div class="prose" style="margin-bottom:30px;"><?= lang_field($page, 'content') ?></div>
        <?php if ($teachers): ?>
        <div class="card-grid">
            <?php foreach ($teachers as $t): ?>
            <a class="card teacher-card card-link" href="<?= h(clean_public_url('/teacher-profile?id=' . (int) $t['id'])) ?>">
                <?php $identity = teacher_identity($t); ?>
                <div class="tid" data-themes="<?= h(implode(',', $identity['themes'])) ?>" data-tags="<?= h(json_encode($identity['tags'], JSON_UNESCAPED_UNICODE)) ?>">
                    <img src="<?= h($t['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="<?= h($t['name']) ?>" crossorigin="anonymous">
                </div>
                <div class="card-body">
                    <div class="name"><?= h($t['name']) ?></div>
                    <div class="position"><?= h(lang_field($t, 'position')) ?></div>
                    <?php if ($t['phone'] || $t['email']): ?>
                    <div class="teacher-contact">
                        <?php if ($t['phone']): ?><span><?= h(t('teachers.phone_prefix')) ?>: <?= h($t['phone']) ?></span><?php endif; ?>
                        <?php if ($t['email']): ?><span><?= h($t['email']) ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p><?= h(t('teachers.page_empty')) ?></p>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/js/teacher-fx.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/teacher-fx.js') ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
