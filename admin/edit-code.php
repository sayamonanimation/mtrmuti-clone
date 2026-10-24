<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if (!admin_edit_code_check($_POST['current'] ?? '')) {
        admin_lock_note_failure();
        set_flash('รหัสแก้ไขปัจจุบันไม่ถูกต้อง', 'error');
    } elseif ($action === 'change') {
        $error = admin_edit_code_valid_new((string) ($_POST['code'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        if ($error) {
            set_flash($error, 'error');
        } else {
            admin_set_edit_code((string) $_POST['code']);
            admin_mark_unlocked();
            set_flash('เปลี่ยนรหัสแก้ไขแล้ว ผู้ดูแลคนอื่นที่เปิดหลังบ้านอยู่จะต้องใส่รหัสใหม่');
        }
    } elseif ($action === 'email') {
        $email = trim((string) ($_POST['recovery_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('อีเมลไม่ถูกต้อง', 'error');
        } else {
            set_setting('admin_edit_code_email', mb_strtolower($email));
            set_flash('บันทึกอีเมลสำหรับกู้รหัสแล้ว');
        }
    }
    header('Location: /admin/edit-code.php');
    exit;
}

$adminPageTitle = 'รหัสแก้ไขหลังบ้าน';
require_once __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <p style="margin:0;">🔒 หลังบ้านล็อกด้วย <strong>รหัสแก้ไข</strong> ที่ใช้ร่วมกันทุกบัญชี: หลังล็อกอินด้วยชื่อผู้ใช้ของตัวเองแล้ว ต้องใส่รหัสนี้อีกครั้งก่อนเข้าแก้ไขข้อมูล · อีเมลกู้รหัส: <strong><?= h(admin_edit_code_email_masked() ?: 'ยังไม่ได้ตั้ง') ?></strong></p>
</div>
<div class="admin-card">
    <h3 style="margin-top:0;">เปลี่ยนรหัสแก้ไข</h3>
    <form method="post" style="max-width:420px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="change">
        <div class="form-group"><label>รหัสแก้ไขปัจจุบัน</label><input type="password" name="current" required autocomplete="current-password"></div>
        <div class="form-group"><label>รหัสแก้ไขใหม่ (อย่างน้อย 8 ตัว)</label><input type="password" name="code" required minlength="8" autocomplete="new-password"></div>
        <div class="form-group"><label>ยืนยันรหัสแก้ไขใหม่</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></div>
        <button class="btn" type="submit">เปลี่ยนรหัส</button>
    </form>
</div>
<div class="admin-card">
    <h3 style="margin-top:0;">อีเมลสำหรับกู้รหัส (เมื่อกด "ลืมรหัสแก้ไข?")</h3>
    <form method="post" style="max-width:420px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="email">
        <div class="form-group"><label>อีเมลใหม่</label><input type="email" name="recovery_email" required autocomplete="email"></div>
        <div class="form-group"><label>รหัสแก้ไขปัจจุบัน (ยืนยัน)</label><input type="password" name="current" required autocomplete="current-password"></div>
        <button class="btn btn-ghost" type="submit">บันทึกอีเมล</button>
    </form>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
