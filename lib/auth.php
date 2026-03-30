<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

function ensure_admin_exists(string $username, string $password): void {
    init_db();
    $stmt = db()->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) return;

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = db()->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)');
    $ins->execute([$username, $hash]);
}

function current_user_id(): ?int {
    start_app_session();
    $uid = $_SESSION['user_id'] ?? null;
    return is_int($uid) ? $uid : (is_string($uid) && ctype_digit($uid) ? (int)$uid : null);
}

function require_login(): int {
    $uid = current_user_id();
    if (!$uid) {
        header('Location: /index.php?page=login', true, 303);
        exit;
    }
    return $uid;
}

function login_attempt(string $username, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user) return false;
    if (!password_verify($password, (string)$user['password_hash'])) return false;
    start_app_session();
    $_SESSION['user_id'] = (int)$user['id'];
    return true;
}

function logout(): void {
    start_app_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

