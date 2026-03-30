from __future__ import annotations

import sqlite3
from typing import Optional

from pathlib import Path

from fastapi import APIRouter, Form, Request
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.templating import Jinja2Templates

from src.auth import ensure_initial_admin, get_user_by_username, session_user_id, verify_password
from src.inventory import (
    add_movement,
    create_item,
    get_item_with_stock,
    list_items_with_stock,
    list_movements,
    low_stock_items,
    update_item_threshold,
)
from src.telegram_notifier import maybe_notify_low_stock

router = APIRouter()
templates = Jinja2Templates(directory=str(Path(__file__).resolve().parents[1] / "templates"))


def _redirect(url: str) -> RedirectResponse:
    return RedirectResponse(url=url, status_code=303)


def _require_login(request: Request) -> Optional[int]:
    uid = session_user_id(request.session)
    return uid


@router.get("/", response_class=HTMLResponse)
def home(request: Request):
    ensure_initial_admin()
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    return _redirect("/items")


@router.get("/login", response_class=HTMLResponse)
def login_page(request: Request):
    return templates.TemplateResponse("login.html", {"request": request, "error": None})


@router.post("/login")
def login_submit(request: Request, username: str = Form(...), password: str = Form(...)):
    user = get_user_by_username(username.strip())
    if not user or not verify_password(password, user["password_hash"]):
        return templates.TemplateResponse("login.html", {"request": request, "error": "账号或密码错误"})
    request.session["user_id"] = int(user["id"])
    return _redirect("/items")


@router.post("/logout")
def logout(request: Request):
    request.session.clear()
    return _redirect("/login")


@router.get("/items", response_class=HTMLResponse)
def items_page(request: Request):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    items = list_items_with_stock()
    low = [r for r in items if float(r["stock"]) < float(r["threshold"])]
    return templates.TemplateResponse(
        "items.html",
        {"request": request, "items": items, "low_count": len(low)},
    )


@router.post("/items")
def items_create(
    request: Request,
    name: str = Form(...),
    unit: str = Form("份"),
    threshold: float = Form(0),
):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    try:
        create_item(name=name, unit=unit, threshold=threshold)
    except sqlite3.IntegrityError:
        # 重名直接跳转回去
        pass
    return _redirect("/items")


@router.get("/items/{item_id}", response_class=HTMLResponse)
def item_detail(request: Request, item_id: int):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    item = get_item_with_stock(item_id)
    if not item:
        return _redirect("/items")
    moves = list_movements(item_id=item_id, limit=200)
    return templates.TemplateResponse(
        "item_detail.html",
        {"request": request, "item": item, "moves": moves},
    )


@router.post("/items/{item_id}/threshold")
def item_threshold_update(request: Request, item_id: int, threshold: float = Form(...)):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    update_item_threshold(item_id=item_id, threshold=threshold)
    return _redirect(f"/items/{item_id}")


@router.post("/items/{item_id}/in")
async def item_in(
    request: Request,
    item_id: int,
    qty: float = Form(...),
    note: str = Form(""),
):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    add_movement(item_id=item_id, kind="IN", qty=qty, note=note, created_by=uid)
    item = get_item_with_stock(item_id)
    if item:
        await maybe_notify_low_stock(item)
    return _redirect(f"/items/{item_id}")


@router.post("/items/{item_id}/out")
async def item_out(
    request: Request,
    item_id: int,
    qty: float = Form(...),
    note: str = Form(""),
):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    add_movement(item_id=item_id, kind="OUT", qty=qty, note=note, created_by=uid)
    item = get_item_with_stock(item_id)
    if item:
        await maybe_notify_low_stock(item)
    return _redirect(f"/items/{item_id}")


@router.get("/movements", response_class=HTMLResponse)
def movements_page(request: Request):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    moves = list_movements(item_id=None, limit=300)
    return templates.TemplateResponse("movements.html", {"request": request, "moves": moves})


@router.post("/low-stock/check")
async def low_stock_check(request: Request):
    uid = _require_login(request)
    if not uid:
        return _redirect("/login")
    items = low_stock_items()
    sent = 0
    for it in items:
        if await maybe_notify_low_stock(it):
            sent += 1
    return templates.TemplateResponse(
        "low_stock_result.html",
        {"request": request, "items": items, "sent": sent},
    )

