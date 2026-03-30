from __future__ import annotations

from typing import Optional

from src.db import db_conn


def list_items_with_stock():
    with db_conn() as conn:
        return conn.execute(
            """
            SELECT
              i.*,
              COALESCE(SUM(CASE WHEN m.kind='IN' THEN m.qty WHEN m.kind='OUT' THEN -m.qty END), 0) AS stock
            FROM items i
            LEFT JOIN movements m ON m.item_id = i.id
            GROUP BY i.id
            ORDER BY i.name ASC;
            """
        ).fetchall()


def get_item(item_id: int):
    with db_conn() as conn:
        return conn.execute("SELECT * FROM items WHERE id = ?", (item_id,)).fetchone()


def get_item_with_stock(item_id: int):
    with db_conn() as conn:
        return conn.execute(
            """
            SELECT
              i.*,
              COALESCE(SUM(CASE WHEN m.kind='IN' THEN m.qty WHEN m.kind='OUT' THEN -m.qty END), 0) AS stock
            FROM items i
            LEFT JOIN movements m ON m.item_id = i.id
            WHERE i.id = ?
            GROUP BY i.id;
            """,
            (item_id,),
        ).fetchone()


def create_item(name: str, unit: str, threshold: float) -> int:
    with db_conn() as conn:
        cur = conn.execute(
            "INSERT INTO items(name, unit, threshold) VALUES (?, ?, ?)",
            (name.strip(), unit.strip() or "份", float(threshold)),
        )
        return int(cur.lastrowid)


def update_item_threshold(item_id: int, threshold: float) -> None:
    with db_conn() as conn:
        conn.execute("UPDATE items SET threshold = ? WHERE id = ?", (float(threshold), item_id))


def add_movement(item_id: int, kind: str, qty: float, note: Optional[str], created_by: Optional[int]) -> int:
    with db_conn() as conn:
        cur = conn.execute(
            "INSERT INTO movements(item_id, kind, qty, note, created_by) VALUES (?, ?, ?, ?, ?)",
            (item_id, kind, float(qty), (note or "").strip() or None, created_by),
        )
        return int(cur.lastrowid)


def list_movements(item_id: Optional[int] = None, limit: int = 200):
    with db_conn() as conn:
        if item_id is None:
            return conn.execute(
                """
                SELECT m.*, i.name AS item_name, i.unit AS item_unit
                FROM movements m
                JOIN items i ON i.id = m.item_id
                ORDER BY m.id DESC
                LIMIT ?;
                """,
                (int(limit),),
            ).fetchall()
        return conn.execute(
            """
            SELECT m.*, i.name AS item_name, i.unit AS item_unit
            FROM movements m
            JOIN items i ON i.id = m.item_id
            WHERE m.item_id = ?
            ORDER BY m.id DESC
            LIMIT ?;
            """,
            (item_id, int(limit)),
        ).fetchall()


def low_stock_items():
    with db_conn() as conn:
        return conn.execute(
            """
            SELECT
              i.*,
              COALESCE(SUM(CASE WHEN m.kind='IN' THEN m.qty WHEN m.kind='OUT' THEN -m.qty END), 0) AS stock
            FROM items i
            LEFT JOIN movements m ON m.item_id = i.id
            GROUP BY i.id
            HAVING stock < i.threshold
            ORDER BY (i.threshold - stock) DESC, i.name ASC;
            """
        ).fetchall()
