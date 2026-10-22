<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/admission-stats.php';

ensure_admission_stats();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $num = fn($k) => max(0, (int) ($_POST[$k] ?? 0));

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM admission_stats WHERE id = ?')->execute([$id]);
        set_flash('ลบข้อมูลปีการศึกษานี้แล้ว');
    } elseif ($action === 'add' || $action === 'edit') {
        $year = (int) ($_POST['academic_year'] ?? 0);
        $values = [$num('plan_m6'), $num('plan_voc'), $num('reg_m6'), $num('reg_voc')];
        $dup = $pdo->prepare('SELECT COUNT(*) FROM admission_stats WHERE academic_year = ? AND id <> ?');
        $dup->execute([$year, $action === 'edit' ? $id : 0]);
        if ($year < 2500 || $year > 2700) {
            set_flash('กรุณากรอกปีการศึกษาเป็น พ.ศ. เช่น 2568', 'error');
        } elseif ($dup->fetchColumn()) {
            set_flash('มีข้อมูลปีการศึกษา ' . $year . ' อยู่แล้ว แก้ไขได้ในตารางด้านล่าง', 'error');
        } elseif ($action === 'add') {
            $pdo->prepare('INSERT INTO admission_stats (academic_year, plan_m6, plan_voc, reg_m6, reg_voc) VALUES (?, ?, ?, ?, ?)')
                ->execute(array_merge([$year], $values));
            set_flash('เพิ่มข้อมูลปีการศึกษา ' . $year . ' แล้ว');
        } else {
            $pdo->prepare('UPDATE admission_stats SET academic_year = ?, plan_m6 = ?, plan_voc = ?, reg_m6 = ?, reg_voc = ? WHERE id = ?')
                ->execute(array_merge([$year], $values, [$id]));
            set_flash('บันทึกข้อมูลปีการศึกษา ' . $year . ' แล้ว');
        }
    }
    header('Location: /admin/stats.php');
    exit;
}

$adminPageTitle = 'จัดการสถิติการรับนักศึกษา';
require_once __DIR__ . '/includes/layout-top.php';

$rows = array_reverse(get_admission_stats());
$nextYear = $rows ? $rows[0]['academic_year'] + 1 : (int) date('Y') + 543;
try {
    $legacy = $pdo->query('SELECT year, label, count FROM stats ORDER BY year')->fetchAll();
} catch (PDOException $e) {
    $legacy = [];
}
?>
<div class="admin-card">
    <p style="margin:0;">กรอกตัวเลขปีละ 1 แถว: <strong>แผนรับ</strong> และ <strong>จำนวนที่ขึ้นทะเบียน</strong> แยก ม.6 / ปวช. — ระบบคำนวณยอดรวมและ <strong>ร้อยละ (ขึ้นทะเบียน ÷ แผนรับ)</strong> ให้อัตโนมัติ
        หน้าเว็บแสดงเปรียบเทียบ <?= ADMISSION_COMPARE_YEARS ?> ปีการศึกษา และผู้ชมเลือกดูปีย้อนหลังได้ · <a href="<?= h(clean_public_url('/statistics')) ?>" target="_blank" rel="noopener">ดูหน้าเว็บ ↗</a></p>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มข้อมูลปีการศึกษา</h3>
    <form method="post" class="adm-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <label class="adm-year">ปีการศึกษา<input type="number" name="academic_year" min="2500" max="2700" required value="<?= $nextYear ?>"></label>
        <fieldset><legend>แผนรับนักศึกษา</legend>
            <label>ม.6<input type="number" name="plan_m6" min="0" value="0" required></label>
            <label>ปวช.<input type="number" name="plan_voc" min="0" value="0" required></label>
        </fieldset>
        <fieldset><legend>นักศึกษาที่ขึ้นทะเบียน</legend>
            <label>ม.6<input type="number" name="reg_m6" min="0" value="0" required></label>
            <label>ปวช.<input type="number" name="reg_voc" min="0" value="0" required></label>
        </fieldset>
        <button class="btn" type="submit">+ เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">ข้อมูลทั้งหมด (<?= count($rows) ?> ปี)</h3>
    <?php if ($rows): ?>
    <div class="adm-table-wrap">
    <table class="admin-table adm-table">
        <thead>
            <tr><th rowspan="2">ปีการศึกษา</th><th colspan="2">แผนรับ</th><th colspan="2">ขึ้นทะเบียน</th><th rowspan="2">รวม<br>แผนรับ / ขึ้นทะเบียน / ร้อยละ</th><th rowspan="2"></th></tr>
            <tr><th>ม.6</th><th>ปวช.</th><th>ม.6</th><th>ปวช.</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): $fid = 'adm-' . $r['id']; ?>
            <tr>
                <td><input form="<?= $fid ?>" type="number" name="academic_year" min="2500" max="2700" value="<?= $r['academic_year'] ?>" required></td>
                <td><input form="<?= $fid ?>" type="number" name="plan_m6" min="0" value="<?= $r['plan_m6'] ?>"></td>
                <td><input form="<?= $fid ?>" type="number" name="plan_voc" min="0" value="<?= $r['plan_voc'] ?>"></td>
                <td><input form="<?= $fid ?>" type="number" name="reg_m6" min="0" value="<?= $r['reg_m6'] ?>"><small><?= admission_pct_text($r['pct_m6']) ?></small></td>
                <td><input form="<?= $fid ?>" type="number" name="reg_voc" min="0" value="<?= $r['reg_voc'] ?>"><small><?= admission_pct_text($r['pct_voc']) ?></small></td>
                <td class="adm-total"><?= $r['plan_total'] ?> / <?= $r['reg_total'] ?> / <strong><?= admission_pct_text($r['pct_total']) ?></strong></td>
                <td class="actions-row">
                    <form method="post" id="<?= $fid ?>">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                    <form method="post" data-confirm="ลบข้อมูลปีการศึกษา <?= $r['academic_year'] ?>?">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="field-hint">ตัวเลขเล็กใต้ช่องขึ้นทะเบียน = ร้อยละเทียบแผนรับของกลุ่มนั้น (อัปเดตหลังกดบันทึก)</p>
    <?php else: ?>
    <p class="field-hint" style="margin:0;">ยังไม่มีข้อมูล เริ่มจากเพิ่มปีการศึกษาด้านบน</p>
    <?php endif; ?>
</div>

<?php if ($legacy): ?>
<div class="admin-card">
    <h3 style="margin-top:0;">ข้อมูลรูปแบบเดิม (ยังไม่แยก ม.6 / ปวช.)</h3>
    <p class="field-hint" style="margin-top:0;">หน้าเว็บจะแสดงข้อมูลชุดนี้จนกว่าจะเพิ่มข้อมูลแบบใหม่ด้านบนอย่างน้อย 1 ปี ใช้เป็นข้อมูลอ้างอิงตอนกรอกใหม่ได้</p>
    <ul style="margin:0;"><?php foreach ($legacy as $l): ?><li>ปี <?= h($l['year']) ?>: <?= (int) $l['count'] ?> <?= h($l['label']) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
