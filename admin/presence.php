<?php
/** JSON endpoint for admin/assets/presence.js heartbeats - see admin/includes/presence.php. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/presence.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}
csrf_check();

$user = (string) $_SESSION['admin_username'];
// Release the session lock right away so heartbeats never hold up the same
// admin's real page loads or form submits running in parallel.
session_write_close();

$pageKey = substr((string) ($_POST['page'] ?? ''), 0, 300);
$event = (string) ($_POST['event'] ?? 'ping');
if ($pageKey === '' || !in_array($event, ['ping', 'save', 'leave', 'check'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad request']);
    exit;
}

echo json_encode(presence_update($pageKey, $user, $event), JSON_UNESCAPED_UNICODE);
