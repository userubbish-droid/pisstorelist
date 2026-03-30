from __future__ import annotations

import os
from pathlib import Path

from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from starlette.middleware.trustedhost import TrustedHostMiddleware
from starlette.middleware.sessions import SessionMiddleware

from src.db import init_db
from src.auth import ensure_initial_admin
from src.routes import router


def create_app() -> FastAPI:
    load_dotenv()

    app_name = os.getenv("APP_NAME", "食堂厨房库存")
    session_secret = os.getenv("SESSION_SECRET", "dev-secret-change-me")
    allowed_hosts_raw = os.getenv("ALLOWED_HOSTS", "localhost,127.0.0.1")
    allowed_hosts = [h.strip() for h in allowed_hosts_raw.split(",") if h.strip()]

    app = FastAPI(title=app_name)
    app.add_middleware(TrustedHostMiddleware, allowed_hosts=allowed_hosts)
    app.add_middleware(SessionMiddleware, secret_key=session_secret)

    static_dir = Path(__file__).parent / "static"
    static_dir.mkdir(parents=True, exist_ok=True)
    app.mount("/static", StaticFiles(directory=str(static_dir)), name="static")

    init_db()
    ensure_initial_admin()
    app.include_router(router)
    return app


app = create_app()
