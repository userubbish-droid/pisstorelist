<?php
declare(strict_types=1);

// Hostinger Web Hosting（PHP）直接可用的配置文件

const APP_NAME = 'Canteen Kitchen Inventory';

// 数据库类型：'sqlite' 或 'mysql'
// - 共享主机上为了安全与可维护，推荐用 mysql
const DB_DRIVER = 'sqlite';

// SQLite（仅在 DB_DRIVER='sqlite' 时使用）
const DB_PATH = __DIR__ . '/data/app.db';

// MySQL（仅在 DB_DRIVER='mysql' 时使用）
const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = '';
const DB_USER = '';
const DB_PASSWORD = '';

const SESSION_SECRET = 'change-me-please';

// 默认管理员（首次使用建议改密码，或跑 install.php 设置）
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin1234';

// Telegram 低库存提醒（可选；留空则不发送）
const TELEGRAM_BOT_TOKEN = '';
const TELEGRAM_CHAT_ID = '';
const TELEGRAM_THROTTLE_MINUTES = 360;

