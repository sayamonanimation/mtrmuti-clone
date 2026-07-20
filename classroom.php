<?php
$currentSlug = 'classroom';
require_once __DIR__ . '/includes/header.php';

$page = get_page('classroom');
$classrooms = get_classrooms();
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
            <div class="card">
                <?php if ($c['image_path']): ?><img src="<?= h($c['image_path']) ?>" alt="<?= h(lang_field($c, 'name')) ?>"><?php endif; ?>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
