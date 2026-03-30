<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function telegram_enabled(): bool {
    return TELEGRAM_BOT_TOKEN !== '' && TELEGRAM_CHAT_ID !== '';
}

function telegram_send_message(string $text): bool {
    if (!telegram_enabled()) return false;

    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $payload = http_build_query([
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $text,
    ]);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 10,
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    return $res !== false;
}

function notif_state(int $item_id): ?array {
    $stmt = db()->prepare("SELECT * FROM telegram_notif_state WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function notif_touch(int $item_id, float $stock): void {
    $driver = defined('DB_DRIVER') ? (string)DB_DRIVER : 'sqlite';
    if ($driver === 'mysql') {
        $stmt = db()->prepare(
            "INSERT INTO telegram_notif_state(item_id, last_sent_at, last_sent_stock)
             VALUES(?, NOW(), ?)
             ON DUPLICATE KEY UPDATE
               last_sent_at = NOW(),
               last_sent_stock = VALUES(last_sent_stock)"
        );
    } else {
        $stmt = db()->prepare(
            "INSERT INTO telegram_notif_state(item_id, last_sent_at, last_sent_stock)
             VALUES(?, datetime('now'), ?)
             ON CONFLICT(item_id) DO UPDATE SET
               last_sent_at = datetime('now'),
               last_sent_stock = excluded.last_sent_stock"
        );
    }
    $stmt->execute([$item_id, $stock]);
}

function maybe_notify_low_stock(array $item_with_stock): bool {
    if (!telegram_enabled()) return false;

    $stock = (float)$item_with_stock['stock'];
    $threshold = (float)$item_with_stock['threshold'];
    if ($stock >= $threshold) return false;

    $state = notif_state((int)$item_with_stock['id']);
    if ($state && !empty($state['last_sent_at'])) {
        $last = strtotime((string)$state['last_sent_at'] . ' UTC');
        if ($last && (time() - $last) < (TELEGRAM_THROTTLE_MINUTES * 60)) {
            return false;
        }
    }

    $name = (string)$item_with_stock['name'];
    $unit = (string)$item_with_stock['unit'];
    $fmt = fn(float $v) => rtrim(rtrim(sprintf('%.2f', $v), '0'), '.');
    $text =
        "[Low stock alert]\n" .
        "Item: {$name}\n" .
        "Stock: " . $fmt($stock) . "{$unit}\n" .
        "Threshold: " . $fmt($threshold) . "{$unit}\n" .
        "Please restock.";

    if (telegram_send_message($text)) {
        notif_touch((int)$item_with_stock['id'], $stock);
        return true;
    }
    return false;
}

