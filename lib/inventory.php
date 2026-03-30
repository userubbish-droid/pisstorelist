<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function items_with_stock(): array {
    $q = db()->query(
        "SELECT i.*,
                COALESCE(SUM(CASE WHEN m.kind='IN' THEN m.qty WHEN m.kind='OUT' THEN -m.qty END), 0) AS stock
         FROM items i
         LEFT JOIN movements m ON m.item_id = i.id
         GROUP BY i.id
         ORDER BY i.name ASC"
    );
    return $q->fetchAll();
}

function item_with_stock(int $item_id): ?array {
    $stmt = db()->prepare(
        "SELECT i.*,
                COALESCE(SUM(CASE WHEN m.kind='IN' THEN m.qty WHEN m.kind='OUT' THEN -m.qty END), 0) AS stock
         FROM items i
         LEFT JOIN movements m ON m.item_id = i.id
         WHERE i.id = ?
         GROUP BY i.id"
    );
    $stmt->execute([$item_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_item(string $name, string $unit, float $threshold): void {
    $stmt = db()->prepare("INSERT INTO items(name, unit, threshold) VALUES(?,?,?)");
    $stmt->execute([trim($name), trim($unit) ?: '份', $threshold]);
}

function update_threshold(int $item_id, float $threshold): void {
    $stmt = db()->prepare("UPDATE items SET threshold = ? WHERE id = ?");
    $stmt->execute([$threshold, $item_id]);
}

function add_movement(int $item_id, string $kind, float $qty, string $note, ?int $created_by): void {
    $stmt = db()->prepare("INSERT INTO movements(item_id, kind, qty, note, created_by) VALUES(?,?,?,?,?)");
    $note2 = trim($note);
    $stmt->execute([$item_id, $kind, $qty, $note2 !== '' ? $note2 : null, $created_by]);
}

function list_movements(?int $item_id, int $limit = 300): array {
    if ($item_id === null) {
        $stmt = db()->prepare(
            "SELECT m.*, i.name AS item_name, i.unit AS item_unit
             FROM movements m
             JOIN items i ON i.id = m.item_id
             ORDER BY m.id DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    $stmt = db()->prepare(
        "SELECT m.*, i.name AS item_name, i.unit AS item_unit
         FROM movements m
         JOIN items i ON i.id = m.item_id
         WHERE m.item_id = ?
         ORDER BY m.id DESC
         LIMIT ?"
    );
    $stmt->execute([$item_id, $limit]);
    return $stmt->fetchAll();
}

function low_stock_items(): array {
    $q = db()->query(
        "SELECT i.*,
                COALESCE(SUM(CASE WHEN m.kind='IN' THEN m.qty WHEN m.kind='OUT' THEN -m.qty END), 0) AS stock
         FROM items i
         LEFT JOIN movements m ON m.item_id = i.id
         GROUP BY i.id
         HAVING stock < i.threshold
         ORDER BY (i.threshold - stock) DESC, i.name ASC"
    );
    return $q->fetchAll();
}

