<?php
$currentSlug = 'contact';
require_once __DIR__ . '/includes/header.php';
$page = get_page('contact');
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <div class="contact-grid">
            <div class="prose"><?= lang_field($page, 'content') ?></div>
            <div>
                <div class="card">
                    <div class="card-body">
                        <h3><?= h(t('contact.info_heading')) ?></h3>
                        <p><?= h(t('contact.address')) ?>: <?= nl2br(h(get_setting('address'))) ?></p>
                        <p><?= h(t('contact.phone')) ?>: <?= h(get_setting('phone')) ?></p>
                        <p><?= h(t('contact.email')) ?>: <?= h(get_setting('email')) ?></p>
                        <?php if ($fb = get_setting('facebook_url')): ?>
                        <p>Facebook: <a href="<?= h($fb) ?>" target="_blank" rel="noopener"><?= h($fb) ?></a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
