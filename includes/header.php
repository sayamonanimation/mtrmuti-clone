<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$currentSlug = $currentSlug ?? 'home';
$pageTitleOverride = $pageTitleOverride ?? null;
$siteName = get_site_name();
$enrollUrl = get_setting('enroll_url');
$logoPath = get_setting('logo_path');

$otherLang = current_lang() === 'en' ? 'th' : 'en';
$urlParts = parse_url($_SERVER['REQUEST_URI'] ?? '/');
parse_str($urlParts['query'] ?? '', $langQueryParams);
$langQueryParams['lang'] = $otherLang;
$langSwitchUrl = ($urlParts['path'] ?? '/') . '?' . http_build_query($langQueryParams);

// Mourning mode is a site-wide setting only admins can flip (see
// admin/mourning-toggle.php) - it grays out the whole site for every
// visitor, not a per-visitor preference.
$mourningMode = get_setting('mourning_mode', '0') === '1';
?>
<!DOCTYPE html>
<html lang="<?= current_lang() === 'en' ? 'en' : 'th' ?>" class="<?= $mourningMode ? 'mourning-mode' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitleOverride ?? $siteName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="topbar">
        <div class="container topbar-inner">
            <a class="brand" href="/index.php">
                <?php if ($logoPath): ?>
                <img class="brand-logo" src="<?= h($logoPath) ?>" alt="<?= h($siteName) ?>">
                <?php else: ?>
                <span class="brand-mark">MT</span>
                <?php endif; ?>
                <span class="brand-name"><?= h($siteName) ?></span>
            </a>
            <div class="topbar-right">
                <div class="topbar-actions">
                    <?php if (is_admin_logged_in()): ?>
                    <a class="topbar-btn topbar-btn-ghost topbar-btn-auth" href="/admin/dashboard.php"><?= h(t('topbar.dashboard')) ?></a>
                    <?php else: ?>
                    <a class="topbar-btn topbar-btn-ghost topbar-btn-auth" href="/admin/login.php"><?= h(t('topbar.login')) ?></a>
                    <?php endif; ?>
                    <?php if ($enrollUrl): ?>
                    <a class="topbar-btn topbar-btn-solid" href="<?= h($enrollUrl) ?>" target="_blank" rel="noopener"><?= h(t('topbar.enroll')) ?></a>
                    <?php endif; ?>
                    <a class="topbar-btn topbar-btn-ghost topbar-btn-icon" href="<?= h($langSwitchUrl) ?>" title="<?= current_lang() === 'en' ? h(t('topbar.switch_to_th')) : h(t('topbar.switch_to_en')) ?>">
                        <?= current_lang() === 'en' ? 'ไทย' : 'EN' ?>
                    </a>
                    <?php if (is_admin_logged_in()): ?>
                    <form method="post" action="/admin/mourning-toggle.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="redirect_to" value="<?= h($_SERVER['REQUEST_URI'] ?? '/index.php') ?>">
                        <button type="submit" class="topbar-btn topbar-btn-ghost topbar-btn-icon" title="<?= $mourningMode ? h(t('topbar.mourning_off')) : h(t('topbar.mourning_on')) ?>" aria-pressed="<?= $mourningMode ? 'true' : 'false' ?>">&#9680;</button>
                    </form>
                    <?php endif; ?>
                </div>
                <button class="nav-toggle" id="navToggle" aria-label="<?= h(t('topbar.menu')) ?>">&#9776;</button>
            </div>
        </div>
    </div>
    <nav class="main-nav" id="mainNav">
        <div class="container">
            <ul>
                <?php foreach (nav_tree() as $item): ?>
                <?php
                $children = $item['children'] ?? [];
                $isActive = $currentSlug === $item['slug'];
                foreach ($children as $child) {
                    if ($currentSlug === $child['slug']) {
                        $isActive = true;
                    }
                }
                ?>
                <li class="<?= $children ? 'has-dropdown' : '' ?>">
                    <a href="<?= h($item['href']) ?>" class="<?= $isActive ? 'active' : '' ?>"<?= ($children && $item['href'] === '#') ? ' onclick="return false;"' : '' ?>>
                        <?= h($item['label']) ?>
                    </a>
                    <?php if ($children): ?>
                    <ul class="dropdown">
                        <?php foreach ($children as $child): ?>
                        <li>
                            <a href="<?= h($child['href']) ?>" class="<?= $currentSlug === $child['slug'] ? 'active' : '' ?>">
                                <?= h($child['label']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</header>
<main>
