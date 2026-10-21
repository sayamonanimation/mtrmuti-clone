<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$member = alumni_find_reset($token);
$error = '';
if ($member && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) < 8) {
        $error = 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัว';
    } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } else {
        alumni_set_password($member, $new);
        session_regenerate_id(true);
        $_SESSION['alumni_member_id'] = (int) $member['id'];
        $_SESSION['alumni_flash'] = 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว';
        header('Location: ' . clean_public_url('/alumni-dashboard'));
        exit;
    }
}

$currentSlug = 'alumni-hub';
$pageTitleOverride = 'ตั้งรหัสผ่านใหม่';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div class="container"><h1>ตั้งรหัสผ่านใหม่</h1></div></div>
<div class="page-content">
    <div class="container hub-auth">
        <?php if (!$member): ?>
        <div class="hub-form content-card">
            <div class="hub-error">ลิงก์นี้หมดอายุหรือถูกใช้ไปแล้ว</div>
            <p class="hub-muted"><a href="<?= h(clean_public_url('/alumni-forgot')) ?>">ขอลิงก์ตั้งรหัสผ่านใหม่อีกครั้ง</a></p>
        </div>
        <?php else: ?>
        <form method="post" class="hub-form content-card">
            <?php if ($error): ?><div class="hub-error"><?= h($error) ?></div><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= h($token) ?>">
            <p class="hub-muted">บัญชี: <?= h($member['email']) ?></p>
            <label>รหัสผ่านใหม่ (อย่างน้อย 8 ตัว)<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
            <label>ยืนยันรหัสผ่านใหม่<input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></label>
            <button class="btn" type="submit">บันทึกรหัสผ่านใหม่</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
