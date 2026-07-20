<?php
require_once __DIR__ . '/includes/auth.php';

$teacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
$teacher = $teacherId ? get_teacher($teacherId) : null;

if (!$teacher) {
    set_flash('ไม่พบข้อมูลอาจารย์', 'error');
    header('Location: /admin/teachers.php');
    exit;
}

$categories = portfolio_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/teacher-portfolio.php?teacher_id=' . $teacherId);
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM teacher_portfolio WHERE id = ? AND teacher_id = ?');
        $stmt->execute([$id, $teacherId]);
        set_flash('ลบรูปภาพเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $category = $_POST['category'] ?? '';
        $caption = trim($_POST['caption'] ?? '');
        if (!array_key_exists($category, $categories)) {
            set_flash('หมวดหมู่ไม่ถูกต้อง', 'error');
            header('Location: /admin/teacher-portfolio.php?teacher_id=' . $teacherId);
            exit;
        }
        $imagePath = handle_image_upload('image', 'teacher-portfolio');
        if ($imagePath) {
            $stmt = $pdo->prepare('INSERT INTO teacher_portfolio (teacher_id, category, image_path, caption) VALUES (?, ?, ?, ?)');
            $stmt->execute([$teacherId, $category, $imagePath, $caption]);
            set_flash('เพิ่มรูปภาพเรียบร้อยแล้ว');
        } else {
            set_flash('กรุณาเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
        }
    }
    header('Location: /admin/teacher-portfolio.php?teacher_id=' . $teacherId);
    exit;
}

$adminPageTitle = 'รูปภาพประกอบผลงาน: ' . $teacher['name'];
require_once __DIR__ . '/includes/layout-top.php';

$portfolio = get_teacher_portfolio($teacherId);
$byCategory = array_fill_keys(array_keys($categories), []);
foreach ($portfolio as $item) {
    $byCategory[$item['category']][] = $item;
}
?>
<p><a href="/admin/teacher-form.php?id=<?= $teacherId ?>">&larr; กลับไปแก้ไขข้อมูลอาจารย์</a></p>

<?php foreach ($categories as $column => $label): ?>
<div class="admin-card">
    <h3 style="margin-top:0;"><?= h($label) ?></h3>

    <form method="post" enctype="multipart/form-data" style="margin-bottom:20px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="category" value="<?= h($column) ?>">
        <div class="form-group">
            <label>ไฟล์ภาพ</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>คำบรรยายภาพ (ไม่บังคับ)</label>
            <input type="text" name="caption" placeholder="เช่น ชื่อผลงาน หรือปีที่ได้รับ">
        </div>
        <button class="btn btn-sm" type="submit">อัพโหลด</button>
    </form>

    <?php if ($byCategory[$column]): ?>
    <div class="portfolio-thumb-grid">
        <?php foreach ($byCategory[$column] as $item): ?>
        <div class="portfolio-thumb-item">
            <img src="<?= h($item['image_path']) ?>" alt="<?= h($item['caption']) ?>">
            <?php if ($item['caption']): ?><p><?= h($item['caption']) ?></p><?php endif; ?>
            <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="field-hint" style="margin:0;">ยังไม่มีรูปภาพในหมวดนี้</p>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
