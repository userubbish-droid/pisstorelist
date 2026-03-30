from __future__ import annotations

import os
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from typing import Optional

import httpx
from dotenv import load_dotenv

from src.db import db_conn


@dataclass(frozen=True)
class TelegramConfig:
    token: str
    chat_id: str
    throttle_minutes: int


def load_telegram_config() -> Optional[TelegramConfig]:
    load_dotenv()
    token = (os.getenv("TELEGRAM_BOT_TOKEN") or "").strip()
    chat_id = (os.getenv("TELEGRAM_CHAT_ID") or "").strip()
    throttle = int(os.getenv("TELEGRAM_THROTTLE_MINUTES", "360"))
    if not token or not chat_id:
        return None
    return TelegramConfig(token=token, chat_id=chat_id, throttle_minutes=throttle)


def _utcnow() -> datetime:
    return datetime.now(timezone.utc)


def _parse_dt(s: Optional[str]) -> Optional[datetime]:
    if not s:
        return None
    try:
        # sqlite datetime('now') => "YYYY-MM-DD HH:MM:SS"
        return datetime.strptime(s, "%Y-%m-%d %H:%M:%S").replace(tzinfo=timezone.utc)
    except Exception:
        return None


async def send_message(cfg: TelegramConfig, text: str) -> None:
    url = f"https://api.telegram.org/bot{cfg.token}/sendMessage"
    async with httpx.AsyncClient(timeout=10) as client:
        r = await client.post(url, json={"chat_id": cfg.chat_id, "text": text})
        r.raise_for_status()


def _get_state(item_id: int):
    with db_conn() as conn:
        return conn.execute("SELECT * FROM telegram_notif_state WHERE item_id = ?", (item_id,)).fetchone()


def _upsert_state(item_id: int, stock: float) -> None:
    with db_conn() as conn:
        conn.execute(
            """
            INSERT INTO telegram_notif_state(item_id, last_sent_at, last_sent_stock)
            VALUES (?, datetime('now'), ?)
            ON CONFLICT(item_id) DO UPDATE SET
              last_sent_at = datetime('now'),
              last_sent_stock = excluded.last_sent_stock;
            """,
            (item_id, float(stock)),
        )


async def maybe_notify_low_stock(item_row_with_stock) -> bool:
    """
    返回是否实际发送了 Telegram。
    规则：
    - 未配置 Telegram：不发
    - stock >= threshold：不发
    - 低于阈值时：首次必发；之后在 throttle 窗口内不重复发
    """
    cfg = load_telegram_config()
    if not cfg:
        return False

    item_id = int(item_row_with_stock["id"])
    name = str(item_row_with_stock["name"])
    unit = str(item_row_with_stock["unit"])
    threshold = float(item_row_with_stock["threshold"])
    stock = float(item_row_with_stock["stock"])

    if stock >= threshold:
        return False

    state = _get_state(item_id)
    if state:
        last_dt = _parse_dt(state["last_sent_at"])
        if last_dt and _utcnow() - last_dt < timedelta(minutes=cfg.throttle_minutes):
            return False

    text = (
        f"【库存不足提醒】\n"
        f"物品：{name}\n"
        f"当前库存：{stock:g}{unit}\n"
        f"阈值：{threshold:g}{unit}\n"
        f"请及时补货。"
    )
    await send_message(cfg, text)
    _upsert_state(item_id, stock)
    return True
