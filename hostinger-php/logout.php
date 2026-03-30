<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    logout();
}
header('Location: /index.php?page=login', true, 303);
exit;

