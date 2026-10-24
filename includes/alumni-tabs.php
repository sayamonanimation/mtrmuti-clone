<?php /** Tabs shared by alumni.php and outstanding-alumni.php. Expects $alumniTab. */ ?>
<div class="alumni-tabs">
    <a class="<?= $alumniTab === 'all' ? 'active' : '' ?>" href="<?= h(clean_public_url('/alumni')) ?>"><?= h(t('alumni.all')) ?></a>
    <a class="<?= $alumniTab === 'outstanding' ? 'active' : '' ?>" href="<?= h(clean_public_url('/outstanding-alumni')) ?>">🏆 <?= h(t('alumni.outstanding')) ?></a>
</div>
