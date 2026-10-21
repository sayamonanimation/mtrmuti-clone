<?php
require_once __DIR__ . '/includes/auth.php';

ensure_batch_year_columns();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM alumni_batches WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบรุ่นศิษย์เก่าเรียบร้อยแล้ว (รูปภาพในรุ่นนี้ถูกลบไปด้วย)');
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO alumni_batches (name, sort_order, graduation_year) VALUES (?, ?, ?)');
            $stmt->execute([$name, $sortOrder, (int) ($_POST['graduation_year'] ?? 0) ?: null]);
            set_flash('เพิ่มรุ่นศิษย์เก่าเรียบร้อยแล้ว');
        }
    }
    if ($action === 'edit') {
        $stmt = $pdo->prepare('UPDATE alumni_batches SET name = ?, graduation_year = ? WHERE id = ?');
        $stmt->execute([trim($_POST['name'] ?? ''), (int) ($_POST['graduation_year'] ?? 0) ?: null, (int) $_POST['id']]);
        set_flash('บันทึกข้อมูลรุ่นแล้ว');
    }
    header('Location: /admin/alumni.php');
    exit;
}

$adminPageTitle = 'จัดการรุ่นศิษย์เก่า';
require_once __DIR__ . '/includes/layout-top.php';

$batches = get_alumni_batches();
$photoCounts = [];
foreach ($pdo->query('SELECT batch_id, COUNT(*) AS c FROM alumni_photos GROUP BY batch_id') as $row) {
    $photoCounts[$row['batch_id']] = (int) $row['c'];
}
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มรุ่นศิษย์เก่า</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ชื่อรุ่น</label>
            <input type="text" name="name" placeholder="เช่น รุ่นที่ 1 (ปีการศึกษา 2560)" required>
        </div>
        <div class="form-group">
            <label>จบการศึกษาปีการศึกษา</label>
            <input type="number" name="graduation_year" min="2500" max="2700" placeholder="เช่น 2568" style="max-width:160px;">
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">เพิ่มรุ่น</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>ชื่อรุ่น / ปีที่จบ</th><th>จำนวนรูปภาพ</th><th>ลำดับ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
            <tr>
                <td>
                    <form method="post" class="inline-edit-form inline-edit-form-row">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int) $batch['id'] ?>">
                        <input type="text" name="name" value="<?= h($batch['name']) ?>" style="width:170px;">
                        <label class="field-hint">จบปี</label>
                        <input type="number" name="graduation_year" value="<?= h($batch['graduation_year']) ?>" min="2500" max="2700" style="width:80px;">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td><?= $photoCounts[$batch['id']] ?? 0 ?> รูป</td>
                <td><?= h($batch['sort_order']) ?></td>
                <td class="actions-row">
                    <a class="btn btn-sm btn-ghost" href="/admin/alumni-photos.php?batch_id=<?= $batch['id'] ?>">จัดการรูปภาพ</a>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบรุ่นนี้? รูปภาพทั้งหมดในรุ่นจะถูกลบไปด้วย');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $batch['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$batches): ?>
            <tr><td colspan="4">ยังไม่มีรุ่นศิษย์เก่าในระบบ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
