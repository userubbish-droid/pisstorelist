from __future__ import annotations

import os
from typing import Optional

from dotenv import load_dotenv
from passlib.context import CryptContext

from src.db import db_conn

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


def hash_password(password: str) -> str:
    return pwd_context.hash(password)


def verify_password(password: str, password_hash: str) -> bool:
    return pwd_context.verify(password, password_hash)


def get_user_by_username(username: str):
    with db_conn() as conn:
        return conn.execute("SELECT * FROM users WHERE username = ?", (username,)).fetchone()


def get_user_by_id(user_id: int):
    with db_conn() as conn:
        return conn.execute("SELECT * FROM users WHERE id = ?", (user_id,)).fetchone()


def create_user(username: str, password: str) -> int:
    with db_conn() as conn:
        cur = conn.execute(
            "INSERT INTO users(username, password_hash) VALUES (?, ?)",
            (username, hash_password(password)),
        )
        return int(cur.lastrowid)


def ensure_initial_admin() -> None:
    load_dotenv()
    username = os.getenv("ADMIN_USERNAME", "admin")
    password = os.getenv("ADMIN_PASSWORD", "admin1234")

    if get_user_by_username(username):
        return

    create_user(username, password)


def session_user_id(session: dict) -> Optional[int]:
    uid = session.get("user_id")
    if isinstance(uid, int):
        return uid
    if isinstance(uid, str) and uid.isdigit():
        return int(uid)
    return None
