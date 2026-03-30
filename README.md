# pisstorelist（食堂厨房库存）

一个给学校食堂厨房用的简易库存系统：账号登录、记录进货与领用/消耗、库存不足 Telegram 提醒。

## 启动

在 `pisstorelist/` 目录下执行：

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt

copy .env.example .env
uvicorn app:app --reload --host 127.0.0.1 --port 8000
```

浏览器打开 `http://127.0.0.1:8000`。

## 默认账号

首次启动会根据 `.env` 自动创建管理员：

- `ADMIN_USERNAME`（默认 `admin`）
- `ADMIN_PASSWORD`（默认 `admin1234`）

## Telegram 提醒（可选）

在 `.env` 里配置：

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`

当某个物品 **库存 < 阈值** 时会尝试发送提醒，并按 `TELEGRAM_THROTTLE_MINUTES` 节流（默认 360 分钟）。
