<?php
/**
 * Generic renderer for content-only pages.
 * Usage: set $currentSlug, require header.php, require this file, require footer.php.
 */
$page = get_page($currentSlug);
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
</div>
