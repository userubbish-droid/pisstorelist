from __future__ import annotations

import os
import sqlite3
from contextlib import contextmanager
from pathlib import Path

from dotenv import load_dotenv


def _db_path() -> Path:
    load_dotenv()
    raw = os.getenv("DB_PATH", "./data/app.db").strip()
    base = Path(__file__).resolve().parents[1]
    p = Path(raw)
    if p.is_absolute():
        return p
    return (base / p).resolve()


@contextmanager
def db_conn():
    path = _db_path()
    path.parent.mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(str(path))
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON;")
    try:
        yield conn
        conn.commit()
    finally:
        conn.close()


def init_db() -> None:
    with db_conn() as conn:
        conn.executescript(
            """
            CREATE TABLE IF NOT EXISTS users (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              username TEXT NOT NULL UNIQUE,
              password_hash TEXT NOT NULL,
              created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS items (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL UNIQUE,
              unit TEXT NOT NULL DEFAULT '份',
              threshold REAL NOT NULL DEFAULT 0,
              created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS movements (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              item_id INTEGER NOT NULL,
              kind TEXT NOT NULL CHECK(kind IN ('IN','OUT')),
              qty REAL NOT NULL CHECK(qty >= 0),
              note TEXT,
              created_by INTEGER,
              created_at TEXT NOT NULL DEFAULT (datetime('now')),
              FOREIGN KEY(item_id) REFERENCES items(id) ON DELETE CASCADE,
              FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS telegram_notif_state (
              item_id INTEGER PRIMARY KEY,
              last_sent_at TEXT,
              last_sent_stock REAL,
              FOREIGN KEY(item_id) REFERENCES items(id) ON DELETE CASCADE
            );
            """
        )
