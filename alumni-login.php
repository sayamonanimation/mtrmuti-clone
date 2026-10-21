<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();

if (current_alumni_member()) {
    header('Location: ' . clean_public_url('/alumni-dashboard'));
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM alumni_members WHERE email = ?');
    $stmt->execute([mb_strtolower(trim($_POST['email'] ?? ''))]);
    $row = $stmt->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['alumni_member_id'] = (int) $row['id'];
        header('Location: ' . clean_public_url('/alumni-dashboard'));
        exit;
    }
    sleep(1); // slow down password guessing
    $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
}

$currentSlug = 'alumni-hub';
$pageTitleOverride = 'เข้าสู่ระบบศิษย์เก่า';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div class="container"><h1>เข้าสู่ระบบศิษย์เก่า</h1></div></div>
<div class="page-content">
    <div class="container hub-auth">
        <form method="post" class="hub-form content-card">
            <?php if ($error): ?><div class="hub-error"><?= h($error) ?></div><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>อีเมล<input type="email" name="email" required autocomplete="email"></label>
            <label>รหัสผ่าน<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="btn" type="submit">เข้าสู่ระบบ</button>
            <p class="hub-muted">ยังไม่มีบัญชี? <a href="<?= h(clean_public_url('/alumni-register')) ?>">สมัครฝากประวัติ</a></p>
            <p class="hub-muted" style="font-size:.8rem;"><a href="<?= h(clean_public_url('/alumni-forgot')) ?>">ลืมรหัสผ่าน?</a></p>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
