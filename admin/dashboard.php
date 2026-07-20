<?php
require_once __DIR__ . '/includes/auth.php';
$adminPageTitle = 'แดชบอร์ด';
require_once __DIR__ . '/includes/layout-top.php';

$pages = $pdo->query('SELECT * FROM pages ORDER BY id ASC')->fetchAll();
?>
<div class="admin-card">
    <p>สวัสดี, <strong><?= h($_SESSION['admin_username']) ?></strong> — เลือกหน้าเว็บไซต์ที่ต้องการแก้ไขเนื้อหาด้านล่าง หรือจัดการอาจารย์ / แกลอรี่ / สไลด์ / สถิติ ผ่านเมนูด้านซ้าย</p>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>หน้าเว็บไซต์</th>
                <th>อัพเดทล่าสุด</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $p): ?>
            <tr>
                <td><?= h($p['title']) ?> <span style="color:#999;">(<?= h($p['slug']) ?>)</span></td>
                <td><?= h($p['updated_at']) ?></td>
                <td><a class="btn btn-sm" href="/admin/page-edit.php?slug=<?= urlencode($p['slug']) ?>">แก้ไขเนื้อหา</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
