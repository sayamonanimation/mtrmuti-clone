<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!alumni_form_is_bot()) {
        alumni_request_password_reset($_POST['email'] ?? '');
    }
    sleep(1);
    // Same answer whether or not the address has an account.
    $sent = true;
}

$currentSlug = 'alumni-hub';
$pageTitleOverride = 'ลืมรหัสผ่าน';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div class="container"><h1>ลืมรหัสผ่าน</h1></div></div>
<div class="page-content">
    <div class="container hub-auth">
        <?php if ($sent): ?>
        <div class="hub-form content-card">
            <div class="hub-notice">ถ้าอีเมลนี้มีบัญชีอยู่ ระบบได้ส่งลิงก์ตั้งรหัสผ่านใหม่ไปแล้ว กรุณาตรวจกล่องจดหมาย (รวมถึงโฟลเดอร์สแปม/จดหมายขยะ) ลิงก์ใช้ได้ภายใน 1 ชั่วโมง</div>
            <p class="hub-muted">ไม่ได้รับอีเมล? <a href="<?= h(clean_public_url('/alumni-forgot')) ?>">ขอลิงก์ใหม่</a> หรือติดต่อเจ้าหน้าที่สาขา</p>
            <p class="hub-muted"><a href="<?= h(clean_public_url('/alumni-login')) ?>">&larr; กลับไปหน้าเข้าสู่ระบบ</a></p>
        </div>
        <?php else: ?>
        <form method="post" class="hub-form content-card">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="form_started" value="<?= time() ?>">
            <input type="text" name="website_confirm" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">
            <p class="hub-muted">กรอกอีเมลที่ใช้สมัคร ระบบจะส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ให้ทางอีเมล</p>
            <label>อีเมล<input type="email" name="email" required autocomplete="email"></label>
            <button class="btn" type="submit">ส่งลิงก์ตั้งรหัสผ่านใหม่</button>
            <p class="hub-muted"><a href="<?= h(clean_public_url('/alumni-login')) ?>">&larr; กลับไปหน้าเข้าสู่ระบบ</a></p>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
