<?php
$currentSlug = 'subjects';
require_once __DIR__ . '/includes/header.php';

$page = get_page('subjects');
$fields = get_fields_of_study();
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

    <?php if ($fields): ?>
    <div class="container" style="margin-top:30px;">
        <div class="card-grid">
            <?php foreach ($fields as $f): ?>
            <div class="card">
                <?php if ($f['icon_path']): ?><img src="<?= h($f['icon_path']) ?>" alt="<?= h(lang_field($f, 'name')) ?>"><?php endif; ?>
                <div class="card-body">
                    <h3><?= h(lang_field($f, 'name')) ?></h3>
                    <p><?= h(lang_field($f, 'description')) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
