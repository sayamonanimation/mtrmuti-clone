<?php
/** Second step after login: enter (or, the first time, set) the shared edit code. */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$back = $_SESSION['admin_unlock_back'] ?? '/admin/dashboard.php';
if (admin_is_unlocked()) {
    header('Location: ' . $back);
    exit;
}
$firstTime = !admin_edit_code_is_set();
$error = '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = (string) ($_POST['code'] ?? '');
    if ($firstTime) {
        $email = trim((string) ($_POST['recovery_email'] ?? ''));
        $error = admin_edit_code_valid_new($code, (string) ($_POST['confirm'] ?? ''));
        if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'กรุณากรอกอีเมลสำหรับกู้รหัสให้ถูกต้อง';
        }
        if (!$error) {
            admin_set_edit_code($code, mb_strtolower($email));
            admin_mark_unlocked();
            set_flash('ตั้งรหัสแก้ไขหลังบ้านเรียบร้อยแล้ว ผู้ดูแลทุกคนต้องใช้รหัสนี้ก่อนแก้ไขข้อมูล');
            unset($_SESSION['admin_unlock_back']);
            header('Location: ' . $back);
            exit;
        }
    } elseif (($_POST['action'] ?? '') === 'forgot') {
        $notice = admin_send_edit_code_reset($_SESSION['admin_username'] ?? '')
            ? 'ส่งลิงก์ตั้งรหัสใหม่ไปที่ ' . admin_edit_code_email_masked() . ' แล้ว กรุณาตรวจกล่องจดหมาย (รวมถึงสแปม) ลิงก์ใช้ได้ 1 ชั่วโมง'
            : 'ยังไม่ได้ตั้งอีเมลสำหรับกู้รหัส กรุณาติดต่อผู้ดูแลเว็บไซต์';
    } elseif ($wait = admin_lock_wait_left()) {
        $error = 'ใส่รหัสผิดหลายครั้ง กรุณารอ ' . $wait . ' วินาที';
    } elseif (admin_edit_code_check($code)) {
        admin_mark_unlocked();
        unset($_SESSION['admin_unlock_back']);
        header('Location: ' . $back);
        exit;
    } else {
        admin_lock_note_failure();
        $error = 'รหัสแก้ไขไม่ถูกต้อง';
    }
}

$lockTitle = $firstTime ? 'ตั้งรหัสแก้ไขหลังบ้าน' : 'ใส่รหัสเพื่อแก้ไขหลังบ้าน';
require __DIR__ . '/includes/lock-layout.php';
?>
        <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="flash flash-success"><?= h($notice) ?></div><?php endif; ?>
        <?php if ($firstTime): ?>
        <p class="lock-text">ตั้งรหัสที่ผู้ดูแลระบบทุกคนต้องใส่ก่อนเข้าแก้ไขหลังบ้าน (ใช้ร่วมกันทุกบัญชี) และอีเมลสำหรับรับลิงก์เมื่อลืมรหัส</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-group"><label>รหัสแก้ไข (อย่างน้อย 8 ตัว)</label><input type="password" name="code" required minlength="8" autocomplete="new-password" autofocus></div>
            <div class="form-group"><label>ยืนยันรหัสแก้ไข</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></div>
            <div class="form-group"><label>อีเมลสำหรับกู้รหัส</label><input type="email" name="recovery_email" required autocomplete="email"></div>
            <button class="btn" type="submit" style="width:100%;">บันทึกรหัส</button>
        </form>
        <?php else: ?>
        <p class="lock-text">หลังบ้านถูกล็อกไว้ เฉพาะผู้ดูแลที่มีรหัสแก้ไขเท่านั้นที่เข้าแก้ไขข้อมูลได้</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-group"><label>รหัสแก้ไข</label><input type="password" name="code" required autocomplete="current-password" autofocus></div>
            <button class="btn" type="submit" style="width:100%;">ปลดล็อก</button>
        </form>
        <form method="post" class="lock-forgot">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="forgot">
            <button type="submit" class="lock-link">ลืมรหัสแก้ไข?</button>
            <?php if (admin_edit_code_email_masked() !== ''): ?><span>ระบบจะส่งลิงก์ไปที่ <?= h(admin_edit_code_email_masked()) ?></span><?php endif; ?>
        </form>
        <?php endif; ?>
<?php lock_layout_end(); ?>
