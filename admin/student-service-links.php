<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM student_service_links WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบลิงก์เรียบร้อยแล้ว');
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE student_service_links SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('อัพเดทสถานะเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $label = trim($_POST['label'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url = normalize_url($_POST['url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($label !== '' && $url !== '') {
            $stmt = $pdo->prepare('INSERT INTO student_service_links (label, label_en, url, sort_order, is_active) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$label, $labelEn, $url, $sortOrder]);
            set_flash('เพิ่มลิงก์เรียบร้อยแล้ว');
        } else {
            set_flash('กรุณากรอกชื่อลิงก์และ URL', 'error');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $label = trim($_POST['label'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url = normalize_url($_POST['url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE student_service_links SET label = ?, label_en = ?, url = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$label, $labelEn, $url, $sortOrder, $id]);
        set_flash('บันทึกลิงก์เรียบร้อยแล้ว');
    }
    header('Location: /admin/student-service-links.php');
    exit;
}

$adminPageTitle = 'จัดการลิงก์บริการนักศึกษา';
require_once __DIR__ . '/includes/layout-top.php';

$links = get_all_student_service_links();
?>
<p class="field-hint">ลิงก์เหล่านี้จะแสดงเป็นเมนูย่อยภายใต้ "บริการนักศึกษา" ในเมนูหลักของเว็บไซต์ (เช่น ระบบยืม-คืนครุภัณฑ์ หรือลิงก์เว็บไซต์อื่นๆ)</p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มลิงก์ใหม่</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ชื่อเมนู</label>
            <input type="text" name="label" placeholder="เช่น ระบบยืม-คืนครุภัณฑ์" required>
        </div>
        <div class="form-group">
            <label>ชื่อเมนู (English)</label>
            <input type="text" name="label_en" placeholder="e.g. Equipment Loan System">
        </div>
        <div class="form-group">
            <label>URL</label>
            <input type="text" name="url" placeholder="https://..." required>
            <p class="field-hint" style="margin-top:0;">พิมพ์ที่อยู่เว็บไซต์ได้เลย ไม่ต้องมี https:// นำหน้าก็ได้ ระบบจะเติมให้อัตโนมัติ</p>
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>ชื่อเมนู / URL (ไทย/English)</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($links as $l): ?>
            <tr>
                <td>
                    <form method="post" class="inline-edit-form" style="max-width:420px;">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <input type="text" name="label" value="<?= h($l['label']) ?>" placeholder="ชื่อเมนู" style="margin-bottom:6px;">
                        <input type="text" name="label_en" value="<?= h($l['label_en']) ?>" placeholder="Label (English)" style="margin-bottom:6px;">
                        <input type="text" name="url" value="<?= h($l['url']) ?>" placeholder="URL" style="margin-bottom:6px;">
                        <input type="number" name="sort_order" value="<?= h($l['sort_order']) ?>" style="width:80px;" title="ลำดับการแสดงผล">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td><?= $l['is_active'] ? 'แสดงอยู่' : 'ซ่อน' ?></td>
                <td class="actions-row">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit"><?= $l['is_active'] ? 'ซ่อน' : 'แสดง' ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$links): ?>
            <tr><td colspan="3">ยังไม่มีลิงก์เพิ่มเติม</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
