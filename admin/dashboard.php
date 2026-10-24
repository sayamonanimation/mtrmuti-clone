<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_unlocked();   // only the dashboard needs the edit code
$adminPageTitle = 'แดชบอร์ด';
require_once __DIR__ . '/includes/layout-top.php';

$pageUpdated = [];
foreach ($pdo->query('SELECT slug, updated_at FROM pages') as $row) {
    $pageUpdated[$row['slug']] = $row['updated_at'];
}
$groups = admin_nav_groups();
?>
<div class="admin-card">
    <p style="margin:0;">สวัสดี, <strong><?= h($_SESSION['admin_username']) ?></strong> — หมวดและชื่อเมนูด้านล่างดึงมาจาก <strong>เมนูบนหน้าเว็บไซต์</strong> โดยตรง เรียงลำดับเหมือนกัน
        ถ้าเพิ่ม ย้าย หรือเปลี่ยนชื่อเมนูที่ <a href="/admin/nav-menu.php">จัดการเมนูเว็บไซต์</a> หน้านี้และแถบเมนูด้านซ้ายจะเปลี่ยนตามอัตโนมัติ</p>
</div>

<div class="dash-grid">
    <?php foreach ($groups as $group): ?>
    <div class="admin-card dash-group<?= !empty($group['hidden_menu']) ? ' dash-group-muted' : '' ?>">
        <h3><span><?= $group['icon'] ?></span><?= h($group['label']) ?>
            <?php if (!empty($group['public'])): ?><a href="<?= h(clean_public_url($group['public'])) ?>" target="_blank" rel="noopener">ดูหน้าเว็บ ↗</a><?php endif; ?>
        </h3>
        <?php if (!empty($group['hidden_menu'])): ?><p class="field-hint" style="margin:-6px 0 8px;">หน้าเหล่านี้ยังมีอยู่ แต่ไม่ได้อยู่ในเมนูหน้าเว็บ (เข้าถึงได้จากลิงก์ตรง)</p><?php endif; ?>
        <?php foreach ($group['items'] as $it): ?>
        <div class="dash-item<?= !empty($it['sub']) ? ' dash-item-sub' : '' ?>">
            <span><?= !empty($it['sub']) ? '↳ ' : '' ?><?= h($it['label']) ?>
                <?php if (!empty($it['external'])): ?><br><small>ลิงก์ภายนอกในเมนู</small><?php endif; ?>
                <?php if (isset($it['slug']) && !empty($pageUpdated[$it['slug']])): ?><br><small>แก้ไขล่าสุด <?= h($pageUpdated[$it['slug']]) ?></small><?php endif; ?>
            </span>
            <span class="dash-item-actions">
                <?php if (!empty($it['external'])): ?>
                <a class="btn btn-sm btn-ghost" href="<?= h($it['external']) ?>" target="_blank" rel="noopener">เปิด ↗</a>
                <a class="btn btn-sm" href="<?= h($it['href']) ?>">แก้ไขลิงก์</a>
                <?php else: ?>
                <?php if (!empty($it['page'])): ?><a class="btn btn-sm btn-ghost" href="/admin/page-edit.php?slug=<?= urlencode($it['page']) ?>">ข้อความหน้า</a><?php endif; ?>
                <a class="btn btn-sm" href="<?= h($it['href']) ?>"><?= isset($it['slug']) ? 'แก้ไข' : 'จัดการ' ?></a>
                <?php endif; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
