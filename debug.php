<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/auth.php';

// Minimal protection to avoid accidental exposure
$k = (string)($_GET['k'] ?? '');
$expected = substr(hash('sha256', SESSION_SECRET), 0, 10);
if ($k !== $expected) {
    http_response_code(404);
    exit;
}

start_app_session();
$uid = current_user_id();

header('Content-Type: text/plain; charset=utf-8');
echo "debug ok\n";
echo "expected_k={$expected}\n";
echo "session_id=" . session_id() . "\n";
echo "uid=" . ($uid ? (string)$uid : "null") . "\n";
echo "cookie_secure_param=false\n";
echo "SERVER:\n";
$keys = [
    'HTTPS',
    'SERVER_PORT',
    'HTTP_X_FORWARDED_PROTO',
    'HTTP_HOST',
    'REQUEST_URI',
];
foreach ($keys as $key) {
    $v = $_SERVER[$key] ?? '';
    echo "- {$key}=" . (is_string($v) ? $v : json_encode($v)) . "\n";
}

