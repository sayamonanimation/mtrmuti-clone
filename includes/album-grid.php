<?php
/** Album cards (cover, title, date, photo count). Expects $albums from get_gallery_albums(). */
?>
<div class="album-grid">
    <?php foreach ($albums as $a): ?>
    <a class="album-card" href="<?= h(clean_public_url('/album?id=' . (int) $a['id'])) ?>">
        <span class="album-card-stack" aria-hidden="true"></span>
        <span class="album-card-cover">
            <?php if ($a['cover']): ?><img src="<?= h($a['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            <span class="album-card-count">🖼 <?= (int) $a['photo_count'] ?></span>
        </span>
        <span class="album-card-body">
            <strong><?= h(lang_field($a, 'title')) ?></strong>
            <?php if ($a['event_date']): ?><small><?= h(thai_date($a['event_date'])) ?></small><?php endif; ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>
