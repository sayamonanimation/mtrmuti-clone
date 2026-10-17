<?php
/** Requires: $adminPageTitle, and admin/includes/auth.php already loaded. */
$adminNav = [
    ['href' => '/admin/dashboard.php', 'label' => 'แดชบอร์ด'],
    ['href' => '/admin/teachers.php', 'label' => 'อาจารย์'],
    ['href' => '/admin/classrooms.php', 'label' => 'ห้องเรียน'],
    ['href' => '/admin/students.php', 'label' => 'นักศึกษาปัจจุบัน'],
    ['href' => '/admin/alumni.php', 'label' => 'ศิษย์เก่า'],
    ['href' => '/admin/nav-menu.php', 'label' => 'จัดการเมนูเว็บไซต์'],
    ['href' => '/admin/student-work-batches.php', 'label' => 'ผลงานนักศึกษา'],
    ['href' => '/admin/academic-calendar.php', 'label' => 'ปฏิทินการศึกษา'],
    ['href' => '/admin/activity-calendar.php', 'label' => 'ปฏิทินกิจกรรม'],
    ['href' => '/admin/gallery.php', 'label' => 'แกลอรี่ภาพ'],
    ['href' => '/admin/sliders.php', 'label' => 'สไลด์หน้าแรก'],
    ['href' => '/admin/posts.php', 'label' => 'ข่าวประชาสัมพันธ์/กิจกรรม'],
    ['href' => '/admin/stats.php', 'label' => 'สถิติการรับนักศึกษา'],
    ['href' => '/admin/student-year-stats.php', 'label' => 'สถิตินักศึกษาแยกชั้นปี'],
    ['href' => '/admin/fields.php', 'label' => 'รายวิชาที่เปิดสอน'],
    ['href' => '/admin/settings.php', 'label' => 'ตั้งค่าเว็บไซต์'],
    ['href' => '/admin/opendrive.php', 'label' => 'ที่เก็บไฟล์ OpenDrive'],
    ['href' => '/admin/admins.php', 'label' => 'ผู้ดูแลระบบ'],
];
$currentPath = $_SERVER['SCRIPT_NAME'];
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
        <nav>
            <ul>
                <?php foreach ($adminNav as $item): ?>
                <li><a href="<?= h($item['href']) ?>" class="<?= strpos($currentPath, $item['href']) === 0 ? 'active' : '' ?>"><?= h($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <a class="admin-logout" href="/admin/logout.php">ออกจากระบบ</a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <h1><?= h($adminPageTitle ?? '') ?></h1>
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
