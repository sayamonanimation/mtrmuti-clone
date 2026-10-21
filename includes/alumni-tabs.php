<?php /** Tabs shared by alumni.php, outstanding-alumni.php and alumni-hub.php. Expects $alumniTab. */ ?>
<div class="alumni-tabs">
    <a class="<?= $alumniTab === 'all' ? 'active' : '' ?>" href="<?= h(clean_public_url('/alumni')) ?>"><?= h(t('alumni.all')) ?></a>
    <a class="<?= $alumniTab === 'outstanding' ? 'active' : '' ?>" href="<?= h(clean_public_url('/outstanding-alumni')) ?>">🏆 <?= h(t('alumni.outstanding')) ?></a>
    <a class="<?= $alumniTab === 'hub' ? 'active' : '' ?>" href="<?= h(clean_public_url('/alumni-hub')) ?>">💼 <?= h(t('alumni.hub')) ?></a>
</div>
