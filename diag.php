<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
require_once __DIR__ . '/lib/session.php';

// Diagnostic endpoint: safe output only (no secrets).
start_app_session();

// Test session write/read
$_SESSION['__diag_counter'] = (int)($_SESSION['__diag_counter'] ?? 0) + 1;
$counter = (int)$_SESSION['__diag_counter'];

header('Content-Type: text/plain; charset=utf-8');
echo "diag ok\n";
echo "time=" . date('c') . "\n";
echo "session_name=" . session_name() . "\n";
echo "session_id=" . session_id() . "\n";
echo "diag_counter=" . $counter . " (refresh should increase)\n";
echo "has_cookie_header=" . (isset($_SERVER['HTTP_COOKIE']) ? "yes" : "no") . "\n";
echo "cookie_len=" . (isset($_SERVER['HTTP_COOKIE']) ? (string)strlen((string)$_SERVER['HTTP_COOKIE']) : "0") . "\n";
echo "HTTPS=" . (string)($_SERVER['HTTPS'] ?? '') . "\n";
echo "SERVER_PORT=" . (string)($_SERVER['SERVER_PORT'] ?? '') . "\n";
echo "X_FORWARDED_PROTO=" . (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') . "\n";
echo "HOST=" . (string)($_SERVER['HTTP_HOST'] ?? '') . "\n";
echo "URI=" . (string)($_SERVER['REQUEST_URI'] ?? '') . "\n";

