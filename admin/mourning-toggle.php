<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = get_setting('mourning_mode', '0');
    set_setting('mourning_mode', $current === '1' ? '0' : '1');
}

$redirectTo = $_POST['redirect_to'] ?? '/index.php';
// Only allow same-site relative paths, to avoid this becoming an open redirect.
if (strpos($redirectTo, '/') !== 0 || strpos($redirectTo, '//') === 0) {
    $redirectTo = '/index.php';
}

header('Location: ' . $redirectTo);
exit;
