<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/classrooms.php');
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM classrooms WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบข้อมูลห้องเรียนเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $imagePath = handle_image_upload('image', 'classrooms');
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO classrooms (name, description, name_en, description_en, image_path, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $nameEn, $descriptionEn, $imagePath, $sortOrder]);
            set_flash('เพิ่มห้องเรียนเรียบร้อยแล้ว');
        } else {
            set_flash('กรุณากรอกชื่อห้องเรียน', 'error');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $imagePath = handle_image_upload('image', 'classrooms');
        if ($imagePath) {
            $stmt = $pdo->prepare('UPDATE classrooms SET name = ?, description = ?, name_en = ?, description_en = ?, image_path = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$name, $description, $nameEn, $descriptionEn, $imagePath, $sortOrder, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE classrooms SET name = ?, description = ?, name_en = ?, description_en = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$name, $description, $nameEn, $descriptionEn, $sortOrder, $id]);
        }
        set_flash('บันทึกข้อมูลห้องเรียนเรียบร้อยแล้ว');
    }
    header('Location: /admin/classrooms.php');
    exit;
}

$adminPageTitle = 'จัดการห้องเรียน';
require_once __DIR__ . '/includes/layout-top.php';

$classrooms = get_classrooms();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มห้องเรียน</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ชื่อห้องเรียน</label><input type="text" name="name" required></div>
        <div class="form-group"><label>คำอธิบาย</label><textarea name="description"></textarea></div>
        <div class="form-group"><label>ชื่อห้องเรียน (English)</label><input type="text" name="name_en"></div>
        <div class="form-group"><label>คำอธิบาย (English)</label><textarea name="description_en"></textarea></div>
        <div class="form-group"><label>รูปภาพ</label><input type="file" name="image" accept="image/*"></div>
        <div class="form-group"><label>ลำดับการแสดงผล</label><input type="number" name="sort_order" value="0"></div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th></th><th>ข้อมูลห้องเรียน (ไทย/English)</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($classrooms as $c): ?>
            <tr>
                <td><?php if ($c['image_path']): ?><img class="thumb" src="<?= h($c['image_path']) ?>" alt=""><?php endif; ?></td>
                <td>
                    <form method="post" class="inline-edit-form" enctype="multipart/form-data" style="max-width:420px;">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <input type="text" name="name" value="<?= h($c['name']) ?>" placeholder="ชื่อห้องเรียน" style="margin-bottom:6px;">
                        <textarea name="description" placeholder="คำอธิบาย" style="margin-bottom:6px;"><?= h($c['description']) ?></textarea>
                        <input type="text" name="name_en" value="<?= h($c['name_en']) ?>" placeholder="Name (English)" style="margin-bottom:6px;">
                        <textarea name="description_en" placeholder="Description (English)" style="margin-bottom:6px;"><?= h($c['description_en']) ?></textarea>
                        <label class="field-hint">เปลี่ยนรูปภาพ (ไม่บังคับ)</label>
                        <input type="file" name="image" accept="image/*" style="margin-bottom:6px;">
                        <input type="number" name="sort_order" value="<?= h($c['sort_order']) ?>" style="width:80px;" title="ลำดับการแสดงผล">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$classrooms): ?>
            <tr><td colspan="3">ยังไม่มีข้อมูลห้องเรียน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
