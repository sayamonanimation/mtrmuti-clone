<?php
require_once __DIR__ . '/includes/auth.php';

$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
$batch = $batchId ? get_student_work_batch($batchId) : null;

if (!$batch) {
    set_flash('ไม่พบปีการศึกษาที่ต้องการ', 'error');
    header('Location: /admin/student-work-batches.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/student-work-items.php?batch_id=' . $batchId);
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM student_works WHERE id = ? AND batch_id = ?');
        $stmt->execute([$id, $batchId]);
        set_flash('ลบผลงานเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $titleEn = trim($_POST['title_en'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $studentName = trim($_POST['student_name'] ?? '');
        $linkUrl = normalize_url($_POST['link_url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $imagePath = handle_image_upload('image', 'student-works');
        if ($title !== '' && $imagePath) {
            $stmt = $pdo->prepare('INSERT INTO student_works (batch_id, title, title_en, description, description_en, student_name, image_path, link_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$batchId, $title, $titleEn, $description, $descriptionEn, $studentName, $imagePath, $linkUrl, $sortOrder]);
            set_flash('เพิ่มผลงานเรียบร้อยแล้ว');
        } else {
            set_flash('กรุณากรอกชื่อผลงานและเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $titleEn = trim($_POST['title_en'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $studentName = trim($_POST['student_name'] ?? '');
        $linkUrl = normalize_url($_POST['link_url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE student_works SET title = ?, title_en = ?, description = ?, description_en = ?, student_name = ?, link_url = ?, sort_order = ? WHERE id = ? AND batch_id = ?');
        $stmt->execute([$title, $titleEn, $description, $descriptionEn, $studentName, $linkUrl, $sortOrder, $id, $batchId]);
        set_flash('บันทึกผลงานเรียบร้อยแล้ว');
    }
    header('Location: /admin/student-work-items.php?batch_id=' . $batchId);
    exit;
}

$adminPageTitle = 'ผลงานนักศึกษา: ' . $batch['name'];
require_once __DIR__ . '/includes/layout-top.php';

$works = get_student_works($batchId);
?>
<p><a href="/admin/student-work-batches.php">&larr; กลับไปหน้าปีการศึกษา</a></p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มผลงาน</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ชื่อผลงาน</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>ชื่อผลงาน (English)</label>
            <input type="text" name="title_en">
        </div>
        <div class="form-group">
            <label>รายละเอียด</label>
            <textarea name="description"></textarea>
        </div>
        <div class="form-group">
            <label>รายละเอียด (English)</label>
            <textarea name="description_en"></textarea>
        </div>
        <div class="form-group">
            <label>ชื่อนักศึกษาเจ้าของผลงาน (ไม่บังคับ)</label>
            <input type="text" name="student_name">
        </div>
        <div class="form-group">
            <label>ไฟล์ภาพ</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>ลิงก์ผลงาน (ไม่บังคับ)</label>
            <input type="text" name="link_url" placeholder="https://...">
            <p class="field-hint" style="margin-top:0;">ถ้าใส่ลิงก์ไว้ คลิกที่ผลงานในหน้าเว็บจะเปิดลิงก์นี้ (เช่น วิดีโอ, Google Drive, พอร์ตโฟลิโอ) ไม่ต้องมี https:// นำหน้าก็ได้</p>
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">อัพโหลด</button>
    </form>
</div>

<div class="admin-card">
    <?php if ($works): ?>
    <div class="portfolio-thumb-grid">
        <?php foreach ($works as $w): ?>
        <div class="portfolio-thumb-item">
            <img src="<?= h($w['image_path']) ?>" alt="<?= h($w['title']) ?>">
            <form method="post" class="inline-edit-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $w['id'] ?>">
                <input type="text" name="title" value="<?= h($w['title']) ?>" placeholder="ชื่อผลงาน" style="margin-bottom:6px;">
                <input type="text" name="title_en" value="<?= h($w['title_en']) ?>" placeholder="Title (English)" style="margin-bottom:6px;">
                <textarea name="description" placeholder="รายละเอียด" style="margin-bottom:6px;"><?= h($w['description']) ?></textarea>
                <textarea name="description_en" placeholder="Description (English)" style="margin-bottom:6px;"><?= h($w['description_en']) ?></textarea>
                <input type="text" name="student_name" value="<?= h($w['student_name']) ?>" placeholder="ชื่อนักศึกษา" style="margin-bottom:6px;">
                <input type="text" name="link_url" value="<?= h($w['link_url']) ?>" placeholder="ลิงก์ผลงาน (ไม่บังคับ)" style="margin-bottom:6px;">
                <input type="number" name="sort_order" value="<?= h($w['sort_order']) ?>" style="width:80px;" title="ลำดับการแสดงผล">
                <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
            </form>
            <form method="post" onsubmit="return confirm('ยืนยันการลบ?');" style="margin-top:6px;">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $w['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="field-hint" style="margin:0;">ยังไม่มีผลงานในปีการศึกษานี้</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
