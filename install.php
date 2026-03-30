<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/auth.php';

start_app_session();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$lockFile = __DIR__ . '/data/installed.lock';
$isLocked = file_exists($lockFile);

$error = null;
$ok = null;

if ($isLocked && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isLocked) {
        http_response_code(403);
        $error = '已安装完成（已锁定）。如需重新安装，请先删除 data/installed.lock（不推荐）。';
    } else {
    csrf_check();
    $adminUser = trim((string)($_POST['admin_username'] ?? ''));
    $adminPass = (string)($_POST['admin_password'] ?? '');
    $secret = trim((string)($_POST['session_secret'] ?? ''));
    $tgToken = trim((string)($_POST['tg_token'] ?? ''));
    $tgChat = trim((string)($_POST['tg_chat'] ?? ''));
    $throttle = (int)($_POST['tg_throttle'] ?? TELEGRAM_THROTTLE_MINUTES);

    if ($adminUser === '' || $adminPass === '' || $secret === '') {
        $error = '管理员账号/密码、SESSION_SECRET 不能为空';
    } else {
        $new = <<<PHP
<?php
declare(strict_types=1);

const APP_NAME = '食堂厨房库存';
const DB_PATH = __DIR__ . '/data/app.db';
const SESSION_SECRET = '{$secret}';
const ADMIN_USERNAME = '{$adminUser}';
const ADMIN_PASSWORD = '{$adminPass}';
const TELEGRAM_BOT_TOKEN = '{$tgToken}';
const TELEGRAM_CHAT_ID = '{$tgChat}';
const TELEGRAM_THROTTLE_MINUTES = {$throttle};

PHP;
        $written = @file_put_contents(__DIR__ . '/config.php', $new);
        if ($written === false) {
            $error = '写入 config.php 失败：请确认文件权限允许写入';
        } else {
            require __DIR__ . '/config.php';
            init_db();
            ensure_admin_exists(ADMIN_USERNAME, ADMIN_PASSWORD);
            @file_put_contents($lockFile, 'installed ' . date('c'));
            $ok = '安装完成：管理员已创建，数据库已初始化。已自动锁定 install.php（data/installed.lock）。';
        }
    }
    }
}
?>
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>安装 - 食堂厨房库存</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900">
    <main class="max-w-2xl mx-auto px-4 py-8">
      <div class="bg-white border rounded-xl p-6">
        <h1 class="text-xl font-semibold">安装（Hostinger Web Hosting）</h1>
        <p class="text-sm text-slate-600 mt-1">初始化 SQLite 数据库并创建管理员账号</p>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-rose-50 border border-rose-200 text-rose-700 text-sm"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
          <div class="mt-4 p-3 rounded bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            <?= h($ok) ?>
            <div class="mt-2"><a class="underline" href="/index.php?page=login">去登录</a></div>
          </div>
        <?php endif; ?>

        <form class="mt-5 space-y-4" method="post">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>" />
          <div>
            <label class="text-sm text-slate-700">管理员账号</label>
            <input name="admin_username" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(ADMIN_USERNAME) ?>" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">管理员密码</label>
            <input name="admin_password" type="password" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(ADMIN_PASSWORD) ?>" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">SESSION_SECRET（建议随机长字符串）</label>
            <input name="session_secret" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(SESSION_SECRET) ?>" required />
          </div>

          <div class="pt-2 border-t">
            <div class="font-semibold">Telegram（可选）</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
              <div>
                <label class="text-sm text-slate-700">Bot Token</label>
                <input name="tg_token" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(TELEGRAM_BOT_TOKEN) ?>" />
              </div>
              <div>
                <label class="text-sm text-slate-700">Chat ID</label>
                <input name="tg_chat" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(TELEGRAM_CHAT_ID) ?>" />
              </div>
              <div>
                <label class="text-sm text-slate-700">节流分钟</label>
                <input name="tg_throttle" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= (int)TELEGRAM_THROTTLE_MINUTES ?>" />
              </div>
            </div>
          </div>

          <button class="px-4 py-2 rounded bg-slate-900 text-white hover:bg-slate-800" type="submit">初始化并保存配置</button>
        </form>

        <div class="mt-4 text-xs text-slate-500">
          安装完成后会自动锁定（创建 <code class="px-1 bg-slate-100 rounded">data/installed.lock</code>），避免 GitHub 同步后反复暴露安装页。
        </div>
      </div>
    </main>
  </body>
</html>

