<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM student_work_batches WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบปีการศึกษาเรียบร้อยแล้ว (ผลงานในปีนี้ถูกลบไปด้วย)');
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO student_work_batches (name, sort_order) VALUES (?, ?)');
            $stmt->execute([$name, $sortOrder]);
            set_flash('เพิ่มปีการศึกษาเรียบร้อยแล้ว');
        }
    }
    header('Location: /admin/student-work-batches.php');
    exit;
}

$adminPageTitle = 'จัดการผลงานนักศึกษา (แยกตามปีการศึกษา)';
require_once __DIR__ . '/includes/layout-top.php';

$batches = get_student_work_batches();
$workCounts = [];
foreach ($pdo->query('SELECT batch_id, COUNT(*) AS c FROM student_works GROUP BY batch_id') as $row) {
    $workCounts[$row['batch_id']] = (int) $row['c'];
}
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มปีการศึกษา</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ชื่อปีการศึกษา</label>
            <input type="text" name="name" placeholder="เช่น ปีการศึกษา 2567" required>
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">เพิ่มปีการศึกษา</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>ปีการศึกษา</th><th>จำนวนผลงาน</th><th>ลำดับ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
            <tr>
                <td><?= h($batch['name']) ?></td>
                <td><?= $workCounts[$batch['id']] ?? 0 ?> ชิ้น</td>
                <td><?= h($batch['sort_order']) ?></td>
                <td class="actions-row">
                    <a class="btn btn-sm btn-ghost" href="/admin/student-work-items.php?batch_id=<?= $batch['id'] ?>">จัดการผลงาน</a>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบปีการศึกษานี้? ผลงานทั้งหมดในปีนี้จะถูกลบไปด้วย');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $batch['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$batches): ?>
            <tr><td colspan="4">ยังไม่มีปีการศึกษาในระบบ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
