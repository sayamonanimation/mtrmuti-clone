<?php
/**
 * Facebook feed HTML for the homepage's auto-refresh (index.php polls this
 * while it's open, so new Page posts appear without reloading).
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/facebook.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
session_write_close();

if (!facebook_graph_enabled()) {
    http_response_code(404);
    exit;
}
echo facebook_render_posts(facebook_feed());
