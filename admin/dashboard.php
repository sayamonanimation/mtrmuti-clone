<?php
require_once __DIR__ . '/includes/auth.php';
$adminPageTitle = 'แดชบอร์ด';
require_once __DIR__ . '/includes/layout-top.php';

$pageUpdated = [];
foreach ($pdo->query('SELECT slug, updated_at FROM pages') as $row) {
    $pageUpdated[$row['slug']] = $row['updated_at'];
}
$groups = admin_nav_groups();
?>
<div class="admin-card">
    <p style="margin:0;">สวัสดี, <strong><?= h($_SESSION['admin_username']) ?></strong> — เมนูหลังบ้านจัดกลุ่มเหมือนเมนูบนหน้าเว็บไซต์ เลือกหมวดที่ต้องการแก้ไขได้เลย</p>
</div>

<div class="dash-grid">
    <?php foreach ($groups as $group): ?>
    <div class="admin-card dash-group">
        <h3><span><?= $group['icon'] ?></span><?= h($group['label']) ?>
            <?php if (!empty($group['public'])): ?><a href="<?= h(clean_public_url($group['public'])) ?>" target="_blank" rel="noopener">ดูหน้าเว็บ ↗</a><?php endif; ?>
        </h3>
        <?php foreach ($group['items'] as $it): ?>
        <div class="dash-item">
            <span><?= h($it['label']) ?>
                <?php if (isset($it['slug']) && !empty($pageUpdated[$it['slug']])): ?><br><small>แก้ไขล่าสุด <?= h($pageUpdated[$it['slug']]) ?></small><?php endif; ?>
            </span>
            <span class="dash-item-actions">
                <?php if (!empty($it['page'])): ?><a class="btn btn-sm btn-ghost" href="/admin/page-edit.php?slug=<?= urlencode($it['page']) ?>">ข้อความหน้า</a><?php endif; ?>
                <a class="btn btn-sm" href="<?= h($it['href']) ?>"><?= isset($it['slug']) ? 'แก้ไข' : 'จัดการ' ?></a>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
