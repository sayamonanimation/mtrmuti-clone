<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM student_year_stats WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบข้อมูลเรียบร้อยแล้ว');
    } elseif ($action === 'add' || $action === 'edit') {
        $academicYear = trim($_POST['academic_year'] ?? '');
        $year1 = (int) ($_POST['year1_count'] ?? 0);
        $year2 = (int) ($_POST['year2_count'] ?? 0);
        $year3 = (int) ($_POST['year3_count'] ?? 0);
        $year4 = (int) ($_POST['year4_count'] ?? 0);
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($academicYear === '') {
            set_flash('กรุณากรอกปีการศึกษา', 'error');
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO student_year_stats (academic_year, year1_count, year2_count, year3_count, year4_count, sort_order) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE year1_count = VALUES(year1_count), year2_count = VALUES(year2_count), year3_count = VALUES(year3_count), year4_count = VALUES(year4_count), sort_order = VALUES(sort_order)');
            $stmt->execute([$academicYear, $year1, $year2, $year3, $year4, $sortOrder]);
            set_flash('เพิ่มข้อมูลเรียบร้อยแล้ว');
        } else {
            $id = (int) $_POST['id'];
            $stmt = $pdo->prepare('UPDATE student_year_stats SET academic_year = ?, year1_count = ?, year2_count = ?, year3_count = ?, year4_count = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$academicYear, $year1, $year2, $year3, $year4, $sortOrder, $id]);
            set_flash('บันทึกข้อมูลเรียบร้อยแล้ว');
        }
    }
    header('Location: /admin/student-year-stats.php');
    exit;
}

$adminPageTitle = 'สถิตินักศึกษาแยกชั้นปี';
require_once __DIR__ . '/includes/layout-top.php';

$rows = get_student_year_stats();
?>
<p class="field-hint">ข้อมูลนี้แสดงเป็นกราฟในหน้าแรก ใต้หัวข้อ "ข่าวประชาสัมพันธ์ &amp; กิจกรรม" กรอกจำนวนนักศึกษาแต่ละชั้นปีของแต่ละปีการศึกษา</p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มปีการศึกษาใหม่</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ปีการศึกษา</label>
            <input type="text" name="academic_year" placeholder="เช่น 2567" required>
        </div>
        <div class="form-group"><label>ชั้นปีที่ 1</label><input type="number" name="year1_count" value="0" min="0"></div>
        <div class="form-group"><label>ชั้นปีที่ 2</label><input type="number" name="year2_count" value="0" min="0"></div>
        <div class="form-group"><label>ชั้นปีที่ 3</label><input type="number" name="year3_count" value="0" min="0"></div>
        <div class="form-group"><label>ชั้นปีที่ 4</label><input type="number" name="year4_count" value="0" min="0"></div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>ปีการศึกษา / จำนวนแต่ละชั้นปี</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <form method="post" class="inline-edit-form inline-edit-form-row">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <label class="field-hint">ปีการศึกษา</label>
                        <input type="text" name="academic_year" value="<?= h($r['academic_year']) ?>" style="width:90px;">
                        <label class="field-hint">ปี 1</label>
                        <input type="number" name="year1_count" value="<?= h($r['year1_count']) ?>" style="width:70px;" min="0">
                        <label class="field-hint">ปี 2</label>
                        <input type="number" name="year2_count" value="<?= h($r['year2_count']) ?>" style="width:70px;" min="0">
                        <label class="field-hint">ปี 3</label>
                        <input type="number" name="year3_count" value="<?= h($r['year3_count']) ?>" style="width:70px;" min="0">
                        <label class="field-hint">ปี 4</label>
                        <input type="number" name="year4_count" value="<?= h($r['year4_count']) ?>" style="width:70px;" min="0">
                        <label class="field-hint">ลำดับ</label>
                        <input type="number" name="sort_order" value="<?= h($r['sort_order']) ?>" style="width:60px;">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td class="actions-row">
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="2">ยังไม่มีข้อมูล</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
