<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (get_setting('chatbot_enabled', '1') !== '1') {
    echo json_encode(['results' => []]);
    exit;
}

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');
if ($query === '' || mb_strlen($query) > 200) {
    echo json_encode(['results' => []]);
    exit;
}

$results = get_chatbot_search_results($query);
$formatted = array_map(function ($r) {
    $excerpt = mb_substr($r['text'], 0, 160);
    if (mb_strlen($r['text']) > 160) {
        $excerpt .= '...';
    }
    return ['title' => $r['title'], 'excerpt' => $excerpt, 'url' => $r['url']];
}, $results);

echo json_encode(['results' => $formatted], JSON_UNESCAPED_UNICODE);
