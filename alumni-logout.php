<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
unset($_SESSION['alumni_member_id']);
header('Location: ' . clean_public_url('/alumni-hub'));
exit;
