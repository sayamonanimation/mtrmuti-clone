<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบโพสต์เรียบร้อยแล้ว');
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE posts SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('อัพเดทสถานะเรียบร้อยแล้ว');
    }
    header('Location: /admin/posts.php');
    exit;
}

$adminPageTitle = 'จัดการข่าวประชาสัมพันธ์/กิจกรรม';
require_once __DIR__ . '/includes/layout-top.php';

$posts = get_all_posts();
?>
<p><a class="btn" href="/admin/post-form.php">+ เพิ่มโพสต์ใหม่</a></p>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th></th><th>หัวข้อ</th><th>วันที่</th><th>ลำดับ</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td><img class="thumb" src="<?= h($p['image_path']) ?>" alt=""></td>
                <td><?= h($p['title']) ?></td>
                <td><?= h($p['post_date']) ?></td>
                <td><?= h($p['sort_order']) ?></td>
                <td><?= $p['is_active'] ? 'แสดงอยู่' : 'ซ่อน' ?></td>
                <td class="actions-row">
                    <a class="btn btn-sm btn-ghost" href="/admin/post-form.php?id=<?= $p['id'] ?>">แก้ไข</a>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit"><?= $p['is_active'] ? 'ซ่อน' : 'แสดง' ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$posts): ?>
            <tr><td colspan="6">ยังไม่มีโพสต์ประชาสัมพันธ์</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
