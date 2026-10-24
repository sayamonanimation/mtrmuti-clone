<?php
/** Opened from the "forgot edit code" e-mail: set a new edit code. Needs an admin login. */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$valid = admin_edit_code_reset_valid($token);
$error = '';
if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = (string) ($_POST['code'] ?? '');
    $error = admin_edit_code_valid_new($code, (string) ($_POST['confirm'] ?? ''));
    if (!$error) {
        admin_set_edit_code($code);
        admin_mark_unlocked();
        set_flash('ตั้งรหัสแก้ไขใหม่เรียบร้อยแล้ว แจ้งรหัสใหม่ให้ผู้ดูแลคนอื่นทราบด้วย');
        header('Location: /admin/dashboard.php');
        exit;
    }
}
$lockTitle = 'ตั้งรหัสแก้ไขหลังบ้านใหม่';
require __DIR__ . '/includes/lock-layout.php';
?>
        <?php if (!$valid): ?>
        <div class="flash flash-error">ลิงก์นี้หมดอายุหรือถูกใช้ไปแล้ว</div>
        <p class="lock-text"><a href="/admin/unlock.php">กลับไปหน้าใส่รหัส</a> แล้วกด "ลืมรหัสแก้ไข?" เพื่อขอลิงก์ใหม่</p>
        <?php else: ?>
        <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= h($token) ?>">
            <div class="form-group"><label>รหัสแก้ไขใหม่ (อย่างน้อย 8 ตัว)</label><input type="password" name="code" required minlength="8" autocomplete="new-password" autofocus></div>
            <div class="form-group"><label>ยืนยันรหัสแก้ไขใหม่</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></div>
            <button class="btn" type="submit" style="width:100%;">บันทึกรหัสใหม่</button>
        </form>
        <?php endif; ?>
<?php lock_layout_end(); ?>
