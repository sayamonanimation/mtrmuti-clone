<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$existing = $pdo->query('SELECT COUNT(*) AS c FROM admin_users')->fetch();
$alreadyInstalled = $existing['c'] > 0;

$error = '';
$done = false;

if (!$alreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($password !== $confirm) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } else {
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ติดตั้งระบบหลังบ้าน</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body class="admin-body">
<div class="login-wrap">
    <div class="login-box">
        <h1>ตั้งค่าบัญชีผู้ดูแลระบบครั้งแรก</h1>
        <?php if ($alreadyInstalled): ?>
            <p>มีบัญชีผู้ดูแลระบบอยู่แล้ว กรุณาลบไฟล์ <code>admin/install.php</code> ออกจากเซิร์ฟเวอร์เพื่อความปลอดภัย</p>
            <p><a class="btn" href="/admin/index.php">ไปหน้าเข้าสู่ระบบ</a></p>
        <?php elseif ($done): ?>
            <p>สร้างบัญชีผู้ดูแลระบบสำเร็จแล้ว! กรุณาลบไฟล์ <code>admin/install.php</code> ออกจากเซิร์ฟเวอร์ทันทีเพื่อความปลอดภัย</p>
            <p><a class="btn" href="/admin/index.php">ไปหน้าเข้าสู่ระบบ</a></p>
        <?php else: ?>
            <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm" required minlength="8">
                </div>
                <button class="btn" type="submit" style="width:100%;">สร้างบัญชี</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
