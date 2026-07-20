<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/fields.php');
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM fields_of_study WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบข้อมูลเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $iconPath = handle_image_upload('icon', 'fields');
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO fields_of_study (name, description, name_en, description_en, icon_path, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $nameEn, $descriptionEn, $iconPath, $sortOrder]);
            set_flash('เพิ่มรายวิชาที่เปิดสอนเรียบร้อยแล้ว');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE fields_of_study SET name = ?, description = ?, name_en = ?, description_en = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$name, $description, $nameEn, $descriptionEn, $sortOrder, $id]);
        set_flash('บันทึกข้อมูลเรียบร้อยแล้ว');
    }
    header('Location: /admin/fields.php');
    exit;
}

$adminPageTitle = 'จัดการรายวิชาที่เปิดสอน';
require_once __DIR__ . '/includes/layout-top.php';

$fields = $pdo->query('SELECT * FROM fields_of_study ORDER BY sort_order ASC')->fetchAll();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มรายวิชาที่เปิดสอน</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ชื่อรายวิชา</label><input type="text" name="name" required></div>
        <div class="form-group"><label>คำอธิบาย</label><textarea name="description"></textarea></div>
        <div class="form-group"><label>ชื่อรายวิชา (English)</label><input type="text" name="name_en"></div>
        <div class="form-group"><label>คำอธิบาย (English)</label><textarea name="description_en"></textarea></div>
        <div class="form-group"><label>ไอคอน/ภาพประกอบ</label><input type="file" name="icon" accept="image/*"></div>
        <div class="form-group"><label>ลำดับการแสดงผล</label><input type="number" name="sort_order" value="0"></div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th></th><th>ข้อมูลสาขา (ไทย/English)</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($fields as $f): ?>
            <tr>
                <td><?php if ($f['icon_path']): ?><img class="thumb" src="<?= h($f['icon_path']) ?>" alt=""><?php endif; ?></td>
                <td>
                    <form method="post" class="inline-edit-form" style="max-width:420px;">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <input type="text" name="name" value="<?= h($f['name']) ?>" placeholder="ชื่อรายวิชา" style="margin-bottom:6px;">
                        <textarea name="description" placeholder="คำอธิบาย" style="margin-bottom:6px;"><?= h($f['description']) ?></textarea>
                        <input type="text" name="name_en" value="<?= h($f['name_en']) ?>" placeholder="Name (English)" style="margin-bottom:6px;">
                        <textarea name="description_en" placeholder="Description (English)" style="margin-bottom:6px;"><?= h($f['description_en']) ?></textarea>
                        <input type="number" name="sort_order" value="<?= h($f['sort_order']) ?>" style="width:80px;" title="ลำดับการแสดงผล">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$fields): ?>
            <tr><td colspan="3">ยังไม่มีข้อมูลรายวิชาที่เปิดสอน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
