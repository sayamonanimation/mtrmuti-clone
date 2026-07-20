<?php
require_once __DIR__ . '/includes/auth.php';

$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
$batch = $batchId ? get_alumni_batch($batchId) : null;

if (!$batch) {
    set_flash('ไม่พบรุ่นศิษย์เก่าที่ต้องการ', 'error');
    header('Location: /admin/alumni.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/alumni-photos.php?batch_id=' . $batchId);
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM alumni_photos WHERE id = ? AND batch_id = ?');
        $stmt->execute([$id, $batchId]);
        set_flash('ลบรูปภาพเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $caption = trim($_POST['caption'] ?? '');
        $imagePath = handle_image_upload('image', 'alumni');
        if ($imagePath) {
            $stmt = $pdo->prepare('INSERT INTO alumni_photos (batch_id, image_path, caption) VALUES (?, ?, ?)');
            $stmt->execute([$batchId, $imagePath, $caption]);
            set_flash('เพิ่มรูปภาพเรียบร้อยแล้ว');
        } else {
            set_flash('กรุณาเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
        }
    }
    header('Location: /admin/alumni-photos.php?batch_id=' . $batchId);
    exit;
}

$adminPageTitle = 'รูปภาพรุ่น: ' . $batch['name'];
require_once __DIR__ . '/includes/layout-top.php';

$photos = get_alumni_photos($batchId);
?>
<p><a href="/admin/alumni.php">&larr; กลับไปหน้ารุ่นศิษย์เก่า</a></p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มรูปภาพ</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ไฟล์ภาพ</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>คำบรรยายภาพ (ไม่บังคับ)</label>
            <input type="text" name="caption" placeholder="เช่น ภาพหมู่วันรับปริญญา">
        </div>
        <button class="btn" type="submit">อัพโหลด</button>
    </form>
</div>

<div class="admin-card">
    <?php if ($photos): ?>
    <div class="portfolio-thumb-grid">
        <?php foreach ($photos as $photo): ?>
        <div class="portfolio-thumb-item">
            <img src="<?= h($photo['image_path']) ?>" alt="<?= h($photo['caption']) ?>">
            <?php if ($photo['caption']): ?><p><?= h($photo['caption']) ?></p><?php endif; ?>
            <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $photo['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="field-hint" style="margin:0;">ยังไม่มีรูปภาพในรุ่นนี้</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
