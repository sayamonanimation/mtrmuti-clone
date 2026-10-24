<?php /** Small centred card used by the unlock / reset screens. Expects $lockTitle; call lock_layout_end() after the content. */ ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($lockTitle) ?> - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/admin/assets/admin.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/admin.css') ?>">
</head>
<body class="admin-body">
<div class="login-wrap">
    <div class="login-box lock-box">
        <div class="lock-icon" aria-hidden="true">🔒</div>
        <h1><?= h($lockTitle) ?></h1>
        <?php if (!empty($_SESSION['admin_username'])): ?><p class="lock-user">ล็อกอินเป็น <strong><?= h($_SESSION['admin_username']) ?></strong> · <a href="/admin/logout.php">ออกจากระบบ</a></p><?php endif; ?>
<?php function lock_layout_end() { ?>
    </div>
</div>
</body>
</html>
<?php } ?>
