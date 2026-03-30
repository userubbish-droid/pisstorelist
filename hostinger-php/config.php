<?php
// 部署到 Hostinger Web Hosting：直接在这里配置即可。
// install.php 也会引导你填入这些值。

declare(strict_types=1);

// 应用名称
const APP_NAME = '食堂厨房库存';

// SQLite 数据库路径（相对于本文件）
const DB_PATH = __DIR__ . '/data/app.db';

// Session 密钥（建议改成随机长字符串）
const SESSION_SECRET = 'change-me-please';

// 初始管理员（install.php 会创建；如果你手动改也可以）
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin1234';

// Telegram 低库存提醒（可选）
const TELEGRAM_BOT_TOKEN = '';
const TELEGRAM_CHAT_ID = '';
const TELEGRAM_THROTTLE_MINUTES = 360;

