<?php
/** Requires: $adminPageTitle, and admin/includes/auth.php already loaded. */
require_once __DIR__ . '/admin-nav.php';
$adminNavGroups = admin_nav_groups();
$adminNavCurrent = admin_nav_current();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($adminPageTitle ?? 'ระบบหลังบ้าน') ?> - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?= (int) @filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
<link rel="stylesheet" href="/admin/assets/admin.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/admin.css') ?>">
</head>
<body class="admin-body" data-csrf="<?= h(csrf_token()) ?>" data-admin-user="<?= h($_SESSION['admin_username'] ?? '') ?>" data-loaded-at="<?= time() ?>">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">ระบบหลังบ้าน</div>
        <nav class="admin-nav">
            <a href="/admin/dashboard.php" class="admin-nav-home<?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? ' active' : '' ?>">📊 แดชบอร์ด</a>
            <?php foreach ($adminNavGroups as $group): ?>
            <?php $groupActive = false; foreach ($group['items'] as $it) { if (admin_nav_is_active($it)) { $groupActive = true; } } ?>
            <details class="admin-nav-group<?= !empty($group['system']) ? ' admin-nav-system' : '' ?>"<?= $groupActive ? ' open' : '' ?>>
                <summary><span class="admin-nav-icon"><?= $group['icon'] ?></span><?= h($group['label']) ?></summary>
                <ul>
                    <?php foreach ($group['items'] as $it): ?>
                    <li><a href="<?= h($it['href']) ?>" class="<?= admin_nav_is_active($it) ? 'active' : '' ?><?= !empty($it['sub']) ? ' admin-nav-sub' : '' ?>"><?= !empty($it['sub']) ? '↳ ' : '' ?><?= h($it['label']) ?><?= !empty($it['external']) ? ' ↗' : '' ?><?php if (!empty($it['badge']) && ($n = admin_nav_badge($it['badge']))): ?> <span class="admin-nav-badge"><?= $n ?></span><?php endif; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endforeach; ?>
        </nav>
        <a class="admin-logout" href="/admin/lock.php">🔒 ล็อกแดชบอร์ด</a>
        <a class="admin-logout" href="/admin/logout.php">ออกจากระบบ</a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <?php if ($adminNavCurrent): ?><div class="admin-crumb">เมนูหน้าเว็บ: <?= h($adminNavCurrent[0]) ?> › <?= h($adminNavCurrent[1]) ?></div><?php endif; ?>
                <h1><?= h($adminPageTitle ?? '') ?></h1>
            </div>
            <div class="admin-topbar-actions">
                <span class="admin-current-user">ผู้ใช้: <?= h($_SESSION['admin_username'] ?? '') ?></span>
                <span class="presence-online" id="presenceOnline" hidden></span>
                <a href="/index.php" target="_blank" class="btn btn-ghost">ดูหน้าเว็บไซต์</a>
            </div>
        </header>
        <div class="admin-content">
        <div class="presence-banner" id="presenceBanner" role="status" hidden></div>
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="flash flash-<?= h($_SESSION['flash']['type']) ?>"><?= h($_SESSION['flash']['message']) ?></div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['upload_notice'])): ?>
            <div class="flash flash-error"><?= h($_SESSION['upload_notice']) ?></div>
            <?php unset($_SESSION['upload_notice']); ?>
        <?php endif; ?>
