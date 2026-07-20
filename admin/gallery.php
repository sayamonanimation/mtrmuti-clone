<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/gallery.php');
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM gallery WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบภาพเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $caption = trim($_POST['caption'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $imagePath = handle_image_upload('image', 'gallery');
        if ($imagePath) {
            $stmt = $pdo->prepare('INSERT INTO gallery (image_path, caption, category) VALUES (?, ?, ?)');
            $stmt->execute([$imagePath, $caption, $category]);
            set_flash('เพิ่มภาพเรียบร้อยแล้ว');
        } else {
            set_flash('กรุณาเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
        }
    }
    header('Location: /admin/gallery.php');
    exit;
}

$adminPageTitle = 'จัดการแกลอรี่ภาพ';
require_once __DIR__ . '/includes/layout-top.php';

$images = $pdo->query('SELECT * FROM gallery ORDER BY sort_order ASC, created_at DESC')->fetchAll();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มภาพใหม่</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ไฟล์ภาพ</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>คำบรรยายภาพ</label>
            <input type="text" name="caption">
        </div>
        <div class="form-group">
            <label>หมวดหมู่ (ไม่บังคับ)</label>
            <input type="text" name="category">
        </div>
        <button class="btn" type="submit">อัพโหลด</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th></th><th>คำบรรยาย</th><th>หมวดหมู่</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($images as $img): ?>
            <tr>
                <td><img class="thumb" src="<?= h($img['image_path']) ?>" alt=""></td>
                <td><?= h($img['caption']) ?></td>
                <td><?= h($img['category']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $img['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$images): ?>
            <tr><td colspan="4">ยังไม่มีภาพในแกลอรี่</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
