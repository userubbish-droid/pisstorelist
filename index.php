<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/util.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/inventory.php';
require_once __DIR__ . '/lib/telegram.php';

start_app_session();
init_db();

$page = $_GET['page'] ?? '';

// 上传即用：确保默认管理员存在
ensure_admin_exists(ADMIN_USERNAME, ADMIN_PASSWORD);

function render(string $title, string $html, bool $show_nav): void {
    $content = $html;
    $flash = get_flash();
    include __DIR__ . '/templates/layout.php';
    exit;
}

function redirect_to(string $url): void {
    header('Location: ' . $url, true, 303);
    exit;
}

if ($page === '' || $page === 'home') {
    if (current_user_id()) redirect_to('/index.php?page=items');
    redirect_to('/index.php?page=login');
}

if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $u = (string)($_POST['username'] ?? '');
        $p = (string)($_POST['password'] ?? '');
        if (login_attempt($u, $p)) {
            redirect_to('/index.php?page=items');
        }
        $error = '账号或密码错误';
    } else {
        $error = null;
    }

    ob_start(); ?>
    <div class="min-h-[70vh] flex items-center justify-center px-4">
      <div class="w-full max-w-md bg-white border rounded-xl p-6 shadow-sm">
        <h1 class="text-xl font-semibold">登录</h1>
        <p class="text-sm text-slate-600 mt-1">仅供校内食堂厨房使用</p>
        <?php if (!empty($error)) : ?>
          <div class="mt-4 p-3 rounded bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <form class="mt-5 space-y-3" method="post" action="/index.php?page=login">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
          <div>
            <label class="text-sm text-slate-700">账号</label>
            <input name="username" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">密码</label>
            <input name="password" type="password" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <button class="w-full bg-slate-900 text-white rounded px-3 py-2 hover:bg-slate-800" type="submit">登录</button>
        </form>
        <div class="mt-4 text-xs text-slate-500">
          默认管理员：<code class="px-1 bg-slate-100 rounded"><?= htmlspecialchars(ADMIN_USERNAME) ?></code>
        </div>
      </div>
    </div>
    <?php
    $html = (string)ob_get_clean();
    render('登录 - ' . APP_NAME, $html, false);
}

// 下面页面都需要登录
$uid = require_login();

if ($page === 'items') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $name = (string)($_POST['name'] ?? '');
        $unit = (string)($_POST['unit'] ?? 'kg');
        $threshold = (float)($_POST['threshold'] ?? 0);
        if (trim($name) === '') {
            set_flash('名称不能为空');
            redirect_to('/index.php?page=items');
        }
        if ($threshold < 0) $threshold = 0;
        try {
            create_item($name, $unit, $threshold);
            set_flash('已新增物品');
        } catch (Throwable $e) {
            set_flash('新增失败：可能重名或数据无效');
        }
        redirect_to('/index.php?page=items');
    }

    $items = items_with_stock();
    $lowCount = 0;
    foreach ($items as $it) {
        if ((float)$it['stock'] < (float)$it['threshold']) $lowCount++;
    }

    ob_start(); ?>
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <h2 class="text-lg font-semibold">物品库存</h2>
        <p class="text-sm text-slate-600 mt-1">当前低库存：<span class="font-semibold"><?= (int)$lowCount ?></span> 个</p>
      </div>
      <form method="post" action="/index.php?page=low_check">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <button class="text-sm px-3 py-2 rounded border bg-white hover:bg-slate-50" type="submit">检查低库存并提醒</button>
      </form>
    </div>

    <div class="mt-6 bg-white border rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-700">
          <tr>
            <th class="text-left p-3">物品</th>
            <th class="text-left p-3">库存</th>
            <th class="text-left p-3">阈值</th>
            <th class="text-left p-3">单位</th>
            <th class="text-left p-3">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)) : ?>
            <tr class="border-t"><td class="p-6 text-center text-slate-500" colspan="5">还没有物品，先在下方新增。</td></tr>
          <?php else: foreach ($items as $it) :
            $isLow = (float)$it['stock'] < (float)$it['threshold']; ?>
            <tr class="border-t <?= $isLow ? 'bg-amber-50' : '' ?>">
              <td class="p-3 font-medium">
                <a class="hover:underline" href="/index.php?page=item&id=<?= (int)$it['id'] ?>"><?= h((string)$it['name']) ?></a>
              </td>
              <td class="p-3 <?= $isLow ? 'text-amber-700 font-semibold' : '' ?>"><?= h(fmt_num((float)$it['stock'])) ?></td>
              <td class="p-3"><?= h(fmt_num((float)$it['threshold'])) ?></td>
              <td class="p-3"><?= h((string)$it['unit']) ?></td>
              <td class="p-3"><a class="text-slate-700 hover:underline" href="/index.php?page=item&id=<?= (int)$it['id'] ?>">记录进货/领用</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-6 bg-white border rounded-xl p-4">
      <h3 class="font-semibold">新增物品</h3>
      <form class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3" method="post" action="/index.php?page=items">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <div class="md:col-span-2">
          <label class="text-sm text-slate-700">名称</label>
          <input name="name" class="mt-1 w-full border rounded px-3 py-2" placeholder="如：大米" required />
        </div>
        <div>
          <label class="text-sm text-slate-700">单位</label>
          <input name="unit" class="mt-1 w-full border rounded px-3 py-2" placeholder="kg / 包 / 箱" value="kg" />
        </div>
        <div>
          <label class="text-sm text-slate-700">阈值</label>
          <input name="threshold" type="number" step="0.01" class="mt-1 w-full border rounded px-3 py-2" value="0" />
        </div>
        <div class="md:col-span-4">
          <button class="px-4 py-2 rounded bg-slate-900 text-white hover:bg-slate-800" type="submit">新增</button>
        </div>
      </form>
    </div>
    <?php
    $html = (string)ob_get_clean();
    render('物品 - ' . APP_NAME, $html, true);
}

if ($page === 'item') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $item = $id ? item_with_stock($id) : null;
    if (!$item) {
        set_flash('物品不存在');
        redirect_to('/index.php?page=items');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'threshold') {
            $t = (float)($_POST['threshold'] ?? 0);
            if ($t < 0) $t = 0;
            update_threshold($id, $t);
            set_flash('阈值已保存');
            redirect_to('/index.php?page=item&id=' . $id);
        }
        if ($action === 'in' || $action === 'out') {
            $qty = (float)($_POST['qty'] ?? 0);
            $note = (string)($_POST['note'] ?? '');
            if ($qty <= 0) {
                set_flash('数量必须大于 0');
                redirect_to('/index.php?page=item&id=' . $id);
            }
            add_movement($id, $action === 'in' ? 'IN' : 'OUT', $qty, $note, $uid);
            $item2 = item_with_stock($id);
            if ($item2) {
                maybe_notify_low_stock($item2);
            }
            set_flash('已记录');
            redirect_to('/index.php?page=item&id=' . $id);
        }
    }

    $moves = list_movements($id, 200);
    $isLow = (float)$item['stock'] < (float)$item['threshold'];

    ob_start(); ?>
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <div class="text-sm text-slate-600"><a class="hover:underline" href="/index.php?page=items">← 返回物品列表</a></div>
        <h2 class="text-xl font-semibold mt-1"><?= h((string)$item['name']) ?></h2>
        <p class="text-sm text-slate-600 mt-1">
          当前库存：
          <span class="font-semibold <?= $isLow ? 'text-amber-700' : '' ?>">
            <?= h(fmt_num((float)$item['stock'])) ?><?= h((string)$item['unit']) ?>
          </span>
          ，阈值：<?= h(fmt_num((float)$item['threshold'])) ?><?= h((string)$item['unit']) ?>
        </p>
      </div>
    </div>

    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white border rounded-xl p-4">
        <h3 class="font-semibold">记录进货</h3>
        <form class="mt-3 space-y-3" method="post" action="/index.php?page=item&id=<?= $id ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
          <input type="hidden" name="action" value="in" />
          <div>
            <label class="text-sm text-slate-700">数量（<?= h((string)$item['unit']) ?>）</label>
            <input name="qty" type="number" min="0.01" step="0.01" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">备注（可选）</label>
            <input name="note" class="mt-1 w-full border rounded px-3 py-2" placeholder="供应商/单号等" />
          </div>
          <button class="w-full px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700" type="submit">入库</button>
        </form>
      </div>

      <div class="bg-white border rounded-xl p-4">
        <h3 class="font-semibold">记录领用/消耗</h3>
        <form class="mt-3 space-y-3" method="post" action="/index.php?page=item&id=<?= $id ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
          <input type="hidden" name="action" value="out" />
          <div>
            <label class="text-sm text-slate-700">数量（<?= h((string)$item['unit']) ?>）</label>
            <input name="qty" type="number" min="0.01" step="0.01" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">备注（可选）</label>
            <input name="note" class="mt-1 w-full border rounded px-3 py-2" placeholder="用途/班次等" />
          </div>
          <button class="w-full px-4 py-2 rounded bg-rose-600 text-white hover:bg-rose-700" type="submit">出库</button>
        </form>
      </div>

      <div class="bg-white border rounded-xl p-4">
        <h3 class="font-semibold">阈值设置</h3>
        <form class="mt-3 space-y-3" method="post" action="/index.php?page=item&id=<?= $id ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
          <input type="hidden" name="action" value="threshold" />
          <div>
            <label class="text-sm text-slate-700">阈值（<?= h((string)$item['unit']) ?>）</label>
            <input name="threshold" type="number" min="0" step="0.01" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(fmt_num((float)$item['threshold'])) ?>" />
          </div>
          <button class="w-full px-4 py-2 rounded border bg-white hover:bg-slate-50" type="submit">保存</button>
        </form>
        <div class="mt-3 text-xs text-slate-500">当库存低于阈值时，会触发 Telegram 提醒（如果已配置）。</div>
      </div>
    </div>

    <div class="mt-6 bg-white border rounded-xl overflow-hidden">
      <div class="p-4 border-b flex items-center justify-between">
        <h3 class="font-semibold">最近流水</h3>
        <a class="text-sm text-slate-700 hover:underline" href="/index.php?page=movements">查看全部流水</a>
      </div>
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-700">
          <tr>
            <th class="text-left p-3">时间</th>
            <th class="text-left p-3">类型</th>
            <th class="text-left p-3">数量</th>
            <th class="text-left p-3">备注</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($moves)) : ?>
            <tr class="border-t"><td class="p-6 text-center text-slate-500" colspan="4">暂无流水记录。</td></tr>
          <?php else: foreach ($moves as $m): ?>
            <tr class="border-t">
              <td class="p-3 text-slate-600"><?= h((string)$m['created_at']) ?></td>
              <td class="p-3">
                <?php if ($m['kind'] === 'IN') : ?>
                  <span class="text-emerald-700 font-semibold">入库</span>
                <?php else: ?>
                  <span class="text-rose-700 font-semibold">出库</span>
                <?php endif; ?>
              </td>
              <td class="p-3"><?= h(fmt_num((float)$m['qty'])) ?><?= h((string)$item['unit']) ?></td>
              <td class="p-3 text-slate-700"><?= h((string)($m['note'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php
    $html = (string)ob_get_clean();
    render('物品 - ' . APP_NAME, $html, true);
}

if ($page === 'movements') {
    $moves = list_movements(null, 300);
    ob_start(); ?>
    <div>
      <h2 class="text-lg font-semibold">流水（最近 300 条）</h2>
      <p class="text-sm text-slate-600 mt-1">入库/出库都会在这里记录。</p>
    </div>
    <div class="mt-6 bg-white border rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-700">
          <tr>
            <th class="text-left p-3">时间</th>
            <th class="text-left p-3">物品</th>
            <th class="text-left p-3">类型</th>
            <th class="text-left p-3">数量</th>
            <th class="text-left p-3">备注</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($moves)) : ?>
            <tr class="border-t"><td class="p-6 text-center text-slate-500" colspan="5">暂无流水记录。</td></tr>
          <?php else: foreach ($moves as $m): ?>
            <tr class="border-t">
              <td class="p-3 text-slate-600"><?= htmlspecialchars((string)$m['created_at']) ?></td>
              <td class="p-3 font-medium">
                <a class="hover:underline" href="/index.php?page=item&id=<?= (int)$m['item_id'] ?>"><?= htmlspecialchars((string)$m['item_name']) ?></a>
              </td>
              <td class="p-3">
                <?php if ($m['kind'] === 'IN') : ?>
                  <span class="text-emerald-700 font-semibold">入库</span>
                <?php else: ?>
                  <span class="text-rose-700 font-semibold">出库</span>
                <?php endif; ?>
              </td>
              <td class="p-3"><?= htmlspecialchars((string)$m['qty']) ?><?= htmlspecialchars((string)$m['item_unit']) ?></td>
              <td class="p-3 text-slate-700"><?= htmlspecialchars((string)($m['note'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php
    $html = (string)ob_get_clean();
    render('流水 - ' . APP_NAME, $html, true);
}

if ($page === 'account') {
    $user = get_user_by_id($uid);
    if (!$user) {
        set_flash('账号不存在');
        redirect_to('/index.php?page=login');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'change_password') {
            $old = (string)($_POST['old_password'] ?? '');
            $new1 = (string)($_POST['new_password'] ?? '');
            $new2 = (string)($_POST['new_password2'] ?? '');
            if (!password_verify($old, (string)$user['password_hash'])) {
                set_flash('旧密码不正确');
                redirect_to('/index.php?page=account');
            }
            if ($new1 === '' || $new1 !== $new2) {
                set_flash('新密码为空或两次输入不一致');
                redirect_to('/index.php?page=account');
            }
            change_password($uid, $new1);
            set_flash('密码已更新，请重新登录');
            logout();
            redirect_to('/index.php?page=login');
        }

        if ($action === 'create_user') {
            $nu = (string)($_POST['new_username'] ?? '');
            $np = (string)($_POST['new_user_password'] ?? '');
            if ($nu === '' || $np === '') {
                set_flash('新用户账号/密码不能为空');
                redirect_to('/index.php?page=account');
            }
            try {
                create_user($nu, $np);
                set_flash('已创建新用户');
            } catch (Throwable $e) {
                set_flash('创建失败：可能重名或数据无效');
            }
            redirect_to('/index.php?page=account');
        }
    }

    ob_start(); ?>
    <div>
      <h2 class="text-lg font-semibold">账号设置</h2>
      <p class="text-sm text-slate-600 mt-1">当前账号：<span class="font-semibold"><?= h((string)$user['username']) ?></span></p>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white border rounded-xl p-4">
        <h3 class="font-semibold">修改密码</h3>
        <form class="mt-3 space-y-3" method="post" action="/index.php?page=account">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="change_password" />
          <div>
            <label class="text-sm text-slate-700">旧密码</label>
            <input name="old_password" type="password" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">新密码</label>
            <input name="new_password" type="password" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">再输入一次新密码</label>
            <input name="new_password2" type="password" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <button class="px-4 py-2 rounded bg-slate-900 text-white hover:bg-slate-800" type="submit">保存</button>
        </form>
      </div>

      <div class="bg-white border rounded-xl p-4">
        <h3 class="font-semibold">新增用户（可选）</h3>
        <div class="text-xs text-slate-500 mt-1">用于给不同员工独立登录。</div>
        <form class="mt-3 space-y-3" method="post" action="/index.php?page=account">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="create_user" />
          <div>
            <label class="text-sm text-slate-700">新用户账号</label>
            <input name="new_username" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="text-sm text-slate-700">新用户密码</label>
            <input name="new_user_password" type="password" class="mt-1 w-full border rounded px-3 py-2" required />
          </div>
          <button class="px-4 py-2 rounded border bg-white hover:bg-slate-50" type="submit">创建</button>
        </form>
      </div>
    </div>
    <?php
    $html = (string)ob_get_clean();
    render('账号 - ' . APP_NAME, $html, true);
}

if ($page === 'low_check') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_to('/index.php?page=items');
    }
    csrf_check();
    $items = low_stock_items();
    $sent = 0;
    foreach ($items as $it) {
        if (maybe_notify_low_stock($it)) $sent++;
    }
    set_flash("低库存物品：" . count($items) . " 个；本次实际发送 Telegram：{$sent} 条");
    redirect_to('/index.php?page=items');
}

http_response_code(404);
render('404', '<div class="text-slate-600">页面不存在</div>', true);

