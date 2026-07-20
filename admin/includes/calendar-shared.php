<?php
/**
 * Shared implementation for the academic/activity calendar admin pages.
 * Requires: $calendarType ('academic' or 'activity') set by the caller,
 * and admin/includes/auth.php already loaded.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM calendar_links WHERE id = ? AND calendar_type = ?');
        $stmt->execute([$id, $calendarType]);
        set_flash('ลบลิงก์เรียบร้อยแล้ว');
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE calendar_links SET is_active = 1 - is_active WHERE id = ? AND calendar_type = ?');
        $stmt->execute([$id, $calendarType]);
        set_flash('อัพเดทสถานะเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $label = trim($_POST['label'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url = normalize_url($_POST['url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($label !== '' && $url !== '') {
            $stmt = $pdo->prepare('INSERT INTO calendar_links (calendar_type, label, label_en, url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$calendarType, $label, $labelEn, $url, $sortOrder]);
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
        $stmt = $pdo->prepare('UPDATE calendar_links SET label = ?, label_en = ?, url = ?, sort_order = ? WHERE id = ? AND calendar_type = ?');
        $stmt->execute([$label, $labelEn, $url, $sortOrder, $id, $calendarType]);
        set_flash('บันทึกลิงก์เรียบร้อยแล้ว');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

require_once __DIR__ . '/layout-top.php';

$links = get_all_calendar_links($calendarType);
?>
<p class="field-hint">ลิงก์เหล่านี้จะแสดงในหน้า "<?= h($adminPageTitle) ?>" ของเว็บไซต์ (ลิงก์ไปยังหน้าเว็บอื่นๆ เช่น ปฏิทินของมหาวิทยาลัย หรือ Google Calendar)</p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มลิงก์ใหม่</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>ชื่อลิงก์</label>
            <input type="text" name="label" required>
        </div>
        <div class="form-group">
            <label>ชื่อลิงก์ (English)</label>
            <input type="text" name="label_en">
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
        <thead><tr><th>ชื่อลิงก์ / URL (ไทย/English)</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($links as $l): ?>
            <tr>
                <td>
                    <form method="post" class="inline-edit-form" style="max-width:420px;">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <input type="text" name="label" value="<?= h($l['label']) ?>" placeholder="ชื่อลิงก์" style="margin-bottom:6px;">
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
            <tr><td colspan="3">ยังไม่มีลิงก์</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/layout-bottom.php'; ?>
