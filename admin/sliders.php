<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/sliders.php');
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM sliders WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบสไลด์เรียบร้อยแล้ว');
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE sliders SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('อัพเดทสถานะเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $caption = trim($_POST['caption'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $captionEn = trim($_POST['caption_en'] ?? '');
        $subtitleEn = trim($_POST['subtitle_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $imagePath = handle_image_upload('image', 'sliders');
        if ($imagePath) {
            $stmt = $pdo->prepare('INSERT INTO sliders (image_path, caption, subtitle, caption_en, subtitle_en, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
            $stmt->execute([$imagePath, $caption, $subtitle, $captionEn, $subtitleEn, $sortOrder]);
            set_flash('เพิ่มสไลด์เรียบร้อยแล้ว');
        } else {
            set_flash('กรุณาเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $caption = trim($_POST['caption'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $captionEn = trim($_POST['caption_en'] ?? '');
        $subtitleEn = trim($_POST['subtitle_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE sliders SET caption = ?, subtitle = ?, caption_en = ?, subtitle_en = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$caption, $subtitle, $captionEn, $subtitleEn, $sortOrder, $id]);
        set_flash('บันทึกสไลด์เรียบร้อยแล้ว');
    }
    header('Location: /admin/sliders.php');
    exit;
}

$adminPageTitle = 'จัดการสไลด์หน้าแรก';
require_once __DIR__ . '/includes/layout-top.php';

$sliders = $pdo->query('SELECT * FROM sliders ORDER BY sort_order ASC')->fetchAll();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มสไลด์ใหม่</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ไฟล์ภาพ (แนะนำขนาดกว้าง เช่น 1600x600px)</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>หัวข้อหลัก</label>
            <input type="text" name="caption">
        </div>
        <div class="form-group">
            <label>หัวข้อย่อย</label>
            <input type="text" name="subtitle">
        </div>
        <div class="form-group">
            <label>หัวข้อหลัก (English)</label>
            <input type="text" name="caption_en">
        </div>
        <div class="form-group">
            <label>หัวข้อย่อย (English)</label>
            <input type="text" name="subtitle_en">
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">อัพโหลด</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th></th><th>หัวข้อหลัก / หัวข้อย่อย / ลำดับ (ไทย/English)</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($sliders as $s): ?>
            <tr>
                <td><img class="thumb" src="<?= h($s['image_path']) ?>" alt=""></td>
                <td>
                    <form method="post" class="inline-edit-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <input type="text" name="caption" value="<?= h($s['caption']) ?>" placeholder="หัวข้อหลัก" style="margin-bottom:6px;">
                        <input type="text" name="subtitle" value="<?= h($s['subtitle']) ?>" placeholder="หัวข้อย่อย" style="margin-bottom:6px;">
                        <input type="text" name="caption_en" value="<?= h($s['caption_en']) ?>" placeholder="Heading (English)" style="margin-bottom:6px;">
                        <input type="text" name="subtitle_en" value="<?= h($s['subtitle_en']) ?>" placeholder="Subheading (English)" style="margin-bottom:6px;">
                        <input type="number" name="sort_order" value="<?= h($s['sort_order']) ?>" style="width:80px;" title="ลำดับการแสดงผล">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td><?= $s['is_active'] ? 'แสดงอยู่' : 'ซ่อน' ?></td>
                <td class="actions-row">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit"><?= $s['is_active'] ? 'ซ่อน' : 'แสดง' ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$sliders): ?>
            <tr><td colspan="4">ยังไม่มีสไลด์ (หน้าแรกจะแสดงเฉพาะหัวข้อแบบ WebGL)</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
