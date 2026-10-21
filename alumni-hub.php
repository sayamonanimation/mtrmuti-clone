<?php
$currentSlug = 'alumni-hub';
require_once __DIR__ . '/includes/header.php';

ensure_alumni_hub();
$page = get_page('alumni-hub');
$members = get_approved_alumni_members();
$allSkills = [];
foreach ($members as $m) {
    foreach (alumni_skill_list($m['skills']) as $skill) {
        $allSkills[mb_strtolower($skill)] = $skill;
    }
}
$me = current_alumni_member();
?>
<div class="page-header">
    <div class="container">
        <h1><?= h(lang_field($page, 'title') ?: t('alumni.hub')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
    </div>
</div>
<div class="page-content">
    <div class="container">
        <?php $alumniTab = 'hub'; require __DIR__ . '/includes/alumni-tabs.php'; ?>

        <div class="hub-cta">
            <div>
                <strong>🎓 เป็นศิษย์เก่าสาขาเทคโนโลยีมัลติมีเดีย?</strong>
                <p>ฝากประวัติและผลงานไว้ที่นี่ ให้บริษัทและผู้ว่าจ้างติดต่อคุณได้โดยตรง — สมัครฟรี อัปเดตข้อมูลเองได้ตลอด</p>
            </div>
            <div class="hub-cta-actions">
                <?php if ($me): ?>
                <a class="btn" href="<?= h(clean_public_url('/alumni-dashboard')) ?>">จัดการโปรไฟล์ของฉัน</a>
                <?php else: ?>
                <a class="btn" href="<?= h(clean_public_url('/alumni-register')) ?>">สมัครฝากประวัติ</a>
                <a class="btn hero-fx-btn-ghost" href="<?= h(clean_public_url('/alumni-login')) ?>">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (trim(strip_tags((string) lang_field($page, 'content'))) !== ''): ?>
        <div class="prose" style="margin-bottom:20px;"><?= lang_field($page, 'content') ?></div>
        <?php endif; ?>

        <?php if ($members): ?>
        <div class="hub-filters">
            <input type="search" id="hubSearch" placeholder="ค้นหาชื่อ ทักษะ หรือตำแหน่ง เช่น Animation, Unity, ตัดต่อ" aria-label="ค้นหา">
            <label class="hub-available"><input type="checkbox" id="hubAvailable"> เฉพาะคนที่พร้อมรับงาน</label>
        </div>
        <?php if ($allSkills): ?>
        <div class="hub-skill-chips" id="hubChips">
            <?php foreach (array_slice($allSkills, 0, 18) as $skill): ?>
            <button type="button" class="hub-chip" data-skill="<?= h(mb_strtolower($skill)) ?>"><?= h($skill) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="hub-grid" id="hubGrid">
            <?php foreach ($members as $m): ?>
            <?php $skills = alumni_skill_list($m['skills']); ?>
            <a class="hub-card" href="<?= h(clean_public_url('/alumni-profile?id=' . (int) $m['id'])) ?>"
               data-search="<?= h(mb_strtolower($m['full_name'] . ' ' . $m['headline'] . ' ' . $m['skills'] . ' ' . $m['batch_name'])) ?>"
               data-available="<?= (int) $m['available_for_work'] ?>">
                <span class="hub-card-cover"<?php if ($m['cover_work']): ?> style="background-image:url('<?= h($m['cover_work']) ?>')"<?php endif; ?>></span>
                <span class="hub-card-avatar"><img src="<?= h($m['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="" loading="lazy"></span>
                <?php if ($m['available_for_work']): ?><span class="hub-badge">● พร้อมรับงาน</span><?php endif; ?>
                <span class="hub-card-body">
                    <strong><?= h($m['full_name']) ?></strong>
                    <?php if ($m['headline']): ?><span class="hub-headline"><?= h($m['headline']) ?></span><?php endif; ?>
                    <span class="hub-class"><?= h(trim($m['batch_name'] . ($m['graduation_year'] ? ' · จบปี ' . $m['graduation_year'] : ''), ' ·')) ?></span>
                    <?php if ($skills): ?>
                    <span class="hub-skills"><?php foreach (array_slice($skills, 0, 5) as $s): ?><i><?= h($s) ?></i><?php endforeach; ?></span>
                    <?php endif; ?>
                    <span class="hub-works">🖼 <?= (int) $m['work_count'] ?> ผลงาน</span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
        <p class="hub-empty" id="hubEmpty" hidden>ไม่พบศิษย์เก่าที่ตรงกับการค้นหา</p>
        <?php else: ?>
        <p>ยังไม่มีศิษย์เก่าฝากประวัติไว้ — เป็นคนแรกได้เลย!</p>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var grid = document.getElementById('hubGrid');
    if (!grid) return;
    var search = document.getElementById('hubSearch'), avail = document.getElementById('hubAvailable');
    var chips = document.querySelectorAll('.hub-chip'), empty = document.getElementById('hubEmpty');
    var activeSkill = '';
    function apply() {
        var q = (search.value || '').trim().toLowerCase(), shown = 0;
        grid.querySelectorAll('.hub-card').forEach(function (card) {
            var hay = card.getAttribute('data-search');
            var ok = (!q || hay.indexOf(q) !== -1) && (!activeSkill || hay.indexOf(activeSkill) !== -1) && (!avail.checked || card.getAttribute('data-available') === '1');
            card.hidden = !ok;
            if (ok) shown++;
        });
        empty.hidden = shown > 0;
    }
    search.addEventListener('input', apply);
    avail.addEventListener('change', apply);
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            activeSkill = activeSkill === chip.getAttribute('data-skill') ? '' : chip.getAttribute('data-skill');
            chips.forEach(function (c) { c.classList.toggle('is-active', c.getAttribute('data-skill') === activeSkill); });
            apply();
        });
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
