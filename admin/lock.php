<?php
/** Locks the back office again for this session (account stays signed in). */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();
unset($_SESSION['admin_unlocked'], $_SESSION['admin_unlock_back']);
header('Location: /admin/unlock.php');
exit;
