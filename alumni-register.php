<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();

$error = '';
$old = ['full_name' => '', 'email' => '', 'batch_name' => '', 'graduation_year' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = array_map('trim', array_intersect_key($_POST, $old)) + $old;
    $password = $_POST['password'] ?? '';
    if (alumni_form_is_bot()) {
        $error = 'กรุณาลองใหม่อีกครั้ง';
    } elseif ($old['full_name'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอกชื่อ-นามสกุล และอีเมลให้ถูกต้อง';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร';
    } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
        $error = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM alumni_members WHERE email = ?');
        $stmt->execute([mb_strtolower($old['email'])]);
        if ($stmt->fetchColumn()) {
            $error = 'อีเมลนี้สมัครไว้แล้ว — เข้าสู่ระบบได้เลย';
        } else {
            $stmt = $pdo->prepare('INSERT INTO alumni_members (email, password_hash, full_name, batch_name, graduation_year, contact_email, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([mb_strtolower($old['email']), password_hash($password, PASSWORD_DEFAULT), $old['full_name'], $old['batch_name'], (int) $old['graduation_year'] ?: null, mb_strtolower($old['email']), 'pending']);
            session_regenerate_id(true);
            $_SESSION['alumni_member_id'] = (int) $pdo->lastInsertId();
            $_SESSION['alumni_flash'] = 'สมัครสำเร็จ! กรอกโปรไฟล์และเพิ่มผลงานได้เลย แล้วกด "ส่งให้ตรวจสอบ" เพื่อแสดงบนหน้าเว็บ';
            header('Location: ' . clean_public_url('/alumni-dashboard'));
            exit;
        }
    }
}

$currentSlug = 'alumni-hub';
$pageTitleOverride = 'สมัครฝากประวัติศิษย์เก่า';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div class="container"><h1>สมัครฝากประวัติศิษย์เก่า</h1><p>สร้างบัญชีเพื่อฝากประวัติและผลงาน แก้ไขข้อมูลได้ตลอด</p></div></div>
<div class="page-content">
    <div class="container hub-auth">
        <form method="post" class="hub-form content-card">
            <?php if ($error): ?><div class="hub-error"><?= h($error) ?></div><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="form_started" value="<?= time() ?>">
            <input type="text" name="website_confirm" value="" tabindex="-1" autocomplete="off" class="hub-hp" aria-hidden="true">
            <label>ชื่อ - นามสกุล<input type="text" name="full_name" required maxlength="150" value="<?= h($old['full_name']) ?>"></label>
            <label>อีเมล (ใช้เข้าสู่ระบบ)<input type="email" name="email" required maxlength="190" value="<?= h($old['email']) ?>" autocomplete="email"></label>
            <div class="hub-form-row">
                <label>รุ่น<input type="text" name="batch_name" maxlength="100" placeholder="เช่น MT10" value="<?= h($old['batch_name']) ?>"></label>
                <label>จบปีการศึกษา<input type="number" name="graduation_year" min="2500" max="2700" placeholder="เช่น 2565" value="<?= h($old['graduation_year']) ?>"></label>
            </div>
            <label>รหัสผ่าน (อย่างน้อย 8 ตัว)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <label>ยืนยันรหัสผ่าน<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
            <button class="btn" type="submit">สมัคร</button>
            <p class="hub-muted">มีบัญชีแล้ว? <a href="<?= h(clean_public_url('/alumni-login')) ?>">เข้าสู่ระบบ</a></p>
            <p class="hub-muted" style="font-size:.8rem;">ข้อมูลจะแสดงบนหน้าเว็บหลังได้รับการตรวจสอบจากเจ้าหน้าที่สาขา</p>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
