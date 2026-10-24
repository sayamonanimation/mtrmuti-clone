<?php
require_once __DIR__ . '/includes/auth.php';

ensure_batch_code_columns();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $entryYear = (int) ($_POST['entry_year'] ?? 0) ?: null;
    $courseYears = max(1, min(8, (int) ($_POST['course_years'] ?? 4)));
    $batchNo = mb_substr(trim($_POST['batch_no'] ?? ''), 0, 20);
    // Stored name = the label, so lists elsewhere (and the alumni copy) read "รุ่น 20 รหัส 69".
    $label = batch_label(['batch_no' => $batchNo, 'entry_year' => $entryYear, 'name' => trim($_POST['name'] ?? '')]);

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM student_batches WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบรุ่นนักศึกษาเรียบร้อยแล้ว (รูปภาพในรุ่นนี้ถูกลบไปด้วย)');
    } elseif ($action === 'add') {
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($batchNo === '' || !$entryYear) {
            set_flash('กรุณากรอกรุ่นที่ และเลือกปีการศึกษาที่เข้าศึกษา', 'error');
        } else {
            $stmt = $pdo->prepare('INSERT INTO student_batches (name, sort_order, entry_year, course_years, batch_no) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$label, $sortOrder, $entryYear, $courseYears, $batchNo]);
            set_flash('เพิ่ม ' . $label . ' แล้ว');
        }
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare('UPDATE student_batches SET name = ?, sort_order = ?, entry_year = ?, course_years = ?, batch_no = ? WHERE id = ?');
        $stmt->execute([$label, (int) ($_POST['sort_order'] ?? 0), $entryYear, $courseYears, $batchNo ?: null, (int) $_POST['id']]);
        set_flash('บันทึก ' . $label . ' แล้ว');
    } elseif ($action === 'graduate') {
        if (move_batch_to_alumni((int) $_POST['id'])) {
            set_flash('ย้ายรุ่นนี้ไปเป็นศิษย์เก่าแล้ว (รูปภาพย้ายไปด้วย)');
        } else {
            set_flash('ย้ายไม่สำเร็จ กรุณาลองใหม่', 'error');
        }
    }
    // A saved entry year may already mean the cohort has finished.
    $moved = promote_graduated_batches();
    if ($moved) {
        set_flash('รุ่น ' . implode(', ', $moved) . ' เรียนครบหลักสูตรแล้ว ย้ายไปเป็นศิษย์เก่าอัตโนมัติ');
    }
    header('Location: /admin/students.php');
    exit;
}

$adminPageTitle = 'จัดการรุ่นนักศึกษาปัจจุบัน';
require_once __DIR__ . '/includes/layout-top.php';

$batches = get_student_batches();
$photoCounts = [];
foreach ($pdo->query('SELECT batch_id, COUNT(*) AS c FROM student_photos GROUP BY batch_id') as $row) {
    $photoCounts[$row['batch_id']] = (int) $row['c'];
}
$thisYear = current_academic_year();
$yearOptions = range($thisYear, $thisYear - 7);
?>
<div class="admin-card graduate-info">
    <strong>🎓 เลื่อนเป็นศิษย์เก่าอัตโนมัติ</strong>
    <p>ปีการศึกษาปัจจุบัน: <strong><?= $thisYear ?></strong> (ขึ้นปีการศึกษาใหม่ทุกวันที่ 1 มิถุนายน)</p>
    <p>กรอก <strong>รุ่นที่</strong> และ <strong>ปีการศึกษาที่เข้าศึกษา</strong> — หน้าเว็บแสดงเป็น <strong>"รุ่น 20 รหัส 69"</strong> (รหัส = 2 หลักท้ายของปีที่เข้า) และ <strong>ชั้นปีที่ 1 → 2 → 3 → 4 เลื่อนเองทุก 1 มิ.ย.</strong> ไม่ต้องแก้ข้อมูลหรืออัปรูปใหม่ เมื่อเรียนครบ 4 ปี รุ่นนั้นพร้อมรูปภาพจะย้ายไปหน้า <a href="/admin/alumni.php">ศิษย์เก่า</a> อัตโนมัติในวันขึ้นปีการศึกษาใหม่</p>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มรุ่นนักศึกษา</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>รุ่นที่</label>
            <input type="text" name="batch_no" maxlength="20" placeholder="เช่น 20" required style="max-width:160px;">
        </div>
        <div class="form-group">
            <label>ปีการศึกษาที่เข้าศึกษา (รหัส = 2 หลักท้าย)</label>
            <select name="entry_year" required>
                <?php foreach ($yearOptions as $y): ?><option value="<?= $y ?>"><?= $y ?> → รหัส <?= sprintf('%02d', $y % 100) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>จำนวนปีของหลักสูตร</label>
            <input type="number" name="course_years" value="4" min="1" max="8" style="max-width:120px;">
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล (0 = เรียงตามชั้นปีอัตโนมัติ)</label>
            <input type="number" name="sort_order" value="0" style="max-width:120px;">
        </div>
        <button class="btn" type="submit">เพิ่มรุ่น</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>รุ่น / รหัส</th><th>ชั้นปี</th><th>จะเป็นศิษย์เก่า</th><th>รูปภาพ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
            <?php $level = batch_year_level($batch); $grad = batch_graduation_year($batch); ?>
            <tr>
                <td>
                    <form method="post" class="inline-edit-form inline-edit-form-row">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int) $batch['id'] ?>">
                        <input type="hidden" name="name" value="<?= h($batch['name']) ?>">
                        <label class="field-hint">รุ่นที่</label>
                        <input type="text" name="batch_no" value="<?= h($batch['batch_no'] ?? '') ?>" maxlength="20" style="width:70px;" title="รุ่นที่" placeholder="20">
                        <label class="field-hint">เข้าปี</label>
                        <select name="entry_year" title="ปีการศึกษาที่เข้าศึกษา">
                            <option value="">— ไม่ระบุ —</option>
                            <?php foreach ($yearOptions as $y): ?><option value="<?= $y ?>"<?= (int) $batch['entry_year'] === $y ? ' selected' : '' ?>><?= $y ?> (รหัส <?= sprintf('%02d', $y % 100) ?>)</option><?php endforeach; ?>
                        </select>
                        <label class="field-hint">หลักสูตร</label>
                        <input type="number" name="course_years" value="<?= (int) ($batch['course_years'] ?: 4) ?>" min="1" max="8" style="width:60px;" title="จำนวนปี">
                        <input type="hidden" name="sort_order" value="<?= (int) $batch['sort_order'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td><strong><?= h(batch_label($batch)) ?></strong><br><?= $level ? '<span class="year-pill">ชั้นปีที่ ' . $level . '</span>' : '<span class="field-hint">⚠️ ระบุรุ่นที่และปีที่เข้า</span>' ?></td>
                <td><?= $grad ? 'หลังจบปีการศึกษา ' . $grad . '<br><span class="field-hint">(1 มิ.ย. ' . ($grad + 1 - 543) . ')</span>' : '<span class="field-hint">ต้องระบุปีที่เข้า</span>' ?></td>
                <td><?= $photoCounts[$batch['id']] ?? 0 ?> รูป</td>
                <td class="actions-row">
                    <a class="btn btn-sm btn-ghost" href="/admin/student-photos.php?batch_id=<?= $batch['id'] ?>">จัดการรูปภาพ</a>
                    <form method="post" onsubmit="return confirm('ย้ายรุ่น <?= h($batch['name']) ?> ไปเป็นศิษย์เก่าตอนนี้เลย?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="graduate">
                        <input type="hidden" name="id" value="<?= $batch['id'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit">🎓 ย้ายเป็นศิษย์เก่าตอนนี้</button>
                    </form>
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
            <tr><td colspan="5">ยังไม่มีรุ่นนักศึกษาในระบบ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
