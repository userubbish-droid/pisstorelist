<?php
declare(strict_types=1);

// Hostinger Web Hosting（PHP）直接可用的配置文件

const APP_NAME = '食堂厨房库存';
const DB_PATH = __DIR__ . '/data/app.db';
const SESSION_SECRET = 'change-me-please';

// 默认管理员（首次使用建议改密码，或跑 install.php 设置）
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin1234';

// Telegram 低库存提醒（可选；留空则不发送）
const TELEGRAM_BOT_TOKEN = '';
const TELEGRAM_CHAT_ID = '';
const TELEGRAM_THROTTLE_MINUTES = 360;

