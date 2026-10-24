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
            set_flash('ตั้งรหัสแก้ไขแดชบอร์ดเรียบร้อยแล้ว');
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

$adminPageTitle = $firstTime ? 'ตั้งรหัสแก้ไขแดชบอร์ด' : 'แดชบอร์ด (ล็อกอยู่)';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card lock-card">
        <div class="lock-icon" aria-hidden="true">🔒</div>
        <h2 class="lock-title"><?= $firstTime ? 'ตั้งรหัสแก้ไขแดชบอร์ด' : 'ใส่รหัสเพื่อเข้าแดชบอร์ด' ?></h2>
        <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="flash flash-success"><?= h($notice) ?></div><?php endif; ?>
        <?php if ($firstTime): ?>
        <p class="lock-text">ตั้งรหัสที่ผู้ดูแลระบบต้องใส่ก่อนเข้าแดชบอร์ด (ใช้ร่วมกันทุกบัญชี) และอีเมลสำหรับรับลิงก์เมื่อลืมรหัส</p>
        <form method="post" data-no-confirm>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-group"><label>รหัสแก้ไข (อย่างน้อย 8 ตัว)</label><input type="password" name="code" required minlength="8" autocomplete="new-password" autofocus></div>
            <div class="form-group"><label>ยืนยันรหัสแก้ไข</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></div>
            <div class="form-group"><label>อีเมลสำหรับกู้รหัส</label><input type="email" name="recovery_email" required autocomplete="email"></div>
            <button class="btn" type="submit" style="width:100%;">บันทึกรหัส</button>
        </form>
        <?php else: ?>
        <p class="lock-text">แดชบอร์ดถูกล็อกไว้ เฉพาะผู้ดูแลที่มีรหัสแก้ไขเท่านั้นที่เข้าได้ · <strong>เมนูอื่น ๆ ในแถบด้านซ้ายใช้งานได้ตามปกติ</strong> ไม่ต้องใส่รหัส</p>
        <form method="post" data-no-confirm>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-group"><label>รหัสแก้ไข</label><input type="password" name="code" required autocomplete="current-password" autofocus></div>
            <button class="btn" type="submit" style="width:100%;">ปลดล็อก</button>
        </form>
        <form method="post" class="lock-forgot" data-no-confirm>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="forgot">
            <button type="submit" class="lock-link">ลืมรหัสแก้ไข?</button>
            <?php if (admin_edit_code_email_masked() !== ''): ?><span>ระบบจะส่งลิงก์ไปที่ <?= h(admin_edit_code_email_masked()) ?></span><?php endif; ?>
        </form>
        <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
