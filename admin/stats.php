<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM stats WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบข้อมูลสถิติเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $year = trim($_POST['year'] ?? '');
        $label = trim($_POST['label'] ?? 'นักศึกษาใหม่');
        $count = (int) ($_POST['count'] ?? 0);
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($year !== '') {
            $stmt = $pdo->prepare('INSERT INTO stats (year, label, count, sort_order) VALUES (?, ?, ?, ?)');
            $stmt->execute([$year, $label, $count, $sortOrder]);
            set_flash('เพิ่มข้อมูลสถิติเรียบร้อยแล้ว');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $year = trim($_POST['year'] ?? '');
        $label = trim($_POST['label'] ?? '');
        $count = (int) ($_POST['count'] ?? 0);
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE stats SET year = ?, label = ?, count = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$year, $label, $count, $sortOrder, $id]);
        set_flash('บันทึกข้อมูลสถิติเรียบร้อยแล้ว');
    }
    header('Location: /admin/stats.php');
    exit;
}

$adminPageTitle = 'จัดการสถิติการรับนักศึกษา';
require_once __DIR__ . '/includes/layout-top.php';

$stats = $pdo->query('SELECT * FROM stats ORDER BY sort_order ASC, year ASC')->fetchAll();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มข้อมูลสถิติ</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ปี (เช่น 2567)</label><input type="text" name="year" required></div>
        <div class="form-group"><label>ป้ายกำกับ</label><input type="text" name="label" value="นักศึกษาใหม่"></div>
        <div class="form-group"><label>จำนวน</label><input type="number" name="count" required></div>
        <div class="form-group"><label>ลำดับการแสดงผล</label><input type="number" name="sort_order" value="0"></div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>ปี / ป้ายกำกับ / จำนวน / ลำดับ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($stats as $s): ?>
            <tr>
                <td>
                    <form method="post" class="inline-edit-form inline-edit-form-row">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <label class="field-hint">ปี</label>
                        <input type="text" name="year" value="<?= h($s['year']) ?>" style="width:80px;">
                        <label class="field-hint">ป้ายกำกับ</label>
                        <input type="text" name="label" value="<?= h($s['label']) ?>" style="width:160px;">
                        <label class="field-hint">จำนวน</label>
                        <input type="number" name="count" value="<?= h($s['count']) ?>" style="width:90px;">
                        <label class="field-hint">ลำดับ</label>
                        <input type="number" name="sort_order" value="<?= h($s['sort_order']) ?>" style="width:70px;">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td class="actions-row">
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$stats): ?>
            <tr><td colspan="2">ยังไม่มีข้อมูลสถิติ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
