<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function start_app_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
    ]);

    session_name('pisstorelist');
    session_start();
}

function csrf_token(): string {
    start_app_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['csrf'];
}

function csrf_check(): void {
    start_app_session();
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$token)) {
        http_response_code(400);
        echo "Bad Request (CSRF)";
        exit;
    }
}

