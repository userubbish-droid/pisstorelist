<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      :root { color-scheme: light; }
      body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }
      .app-shell-bg {
        background:
          radial-gradient(1200px 500px at 20% -10%, #dbeafe 0%, transparent 60%),
          radial-gradient(900px 420px at 100% 0%, #e2e8f0 0%, transparent 55%),
          #f3f6fb;
      }
    </style>
  </head>
  <body class="text-slate-900 app-shell-bg">
    <div class="min-h-screen">
      <?php if (!empty($show_nav)) : ?>
      <header class="md:hidden sticky top-0 z-30 bg-slate-950 text-slate-100 border-b border-slate-800">
        <details class="group">
          <summary class="list-none cursor-pointer px-4 py-3 flex items-center justify-between">
            <div>
              <div class="font-semibold text-sm"><?= htmlspecialchars(APP_NAME) ?></div>
              <div class="text-[11px] text-slate-400">Kitchen inventory</div>
            </div>
            <span class="text-xs px-2 py-1 rounded border border-slate-700 text-slate-300 group-open:hidden">Menu</span>
            <span class="text-xs px-2 py-1 rounded border border-slate-700 text-slate-300 hidden group-open:inline">Close</span>
          </summary>
          <?php $active = (string)($active_page ?? ''); ?>
          <nav class="px-3 pb-3 space-y-1 text-sm border-t border-slate-800">
            <a class="block mt-3 px-3 py-2.5 rounded-lg transition <?= $active==='home' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=home">Home</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='add' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=add">Product</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='record' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=record">Record</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='statement' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=statement">Statement</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='transaction' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=transaction">Transaction</a>
            <div class="h-px bg-slate-800 my-2"></div>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='account' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=account">Account</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='help' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=help">Help</a>
            <form class="pt-2" method="post" action="/logout.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
              <button class="w-full text-sm px-3 py-2.5 rounded-lg border border-slate-700 text-slate-100 bg-slate-900 hover:bg-slate-800 transition" type="submit">Logout</button>
            </form>
          </nav>
        </details>
      </header>
      <div class="flex min-h-screen">
        <aside class="hidden md:flex w-72 bg-slate-950 text-slate-100 border-r border-slate-900 min-h-screen sticky top-0 shadow-xl flex-col">
          <div class="px-5 py-5 border-b border-slate-800">
            <a class="font-semibold text-white tracking-tight text-base" href="/index.php?page=home"><?= htmlspecialchars(APP_NAME) ?></a>
            <div class="text-xs text-slate-400 mt-1">Kitchen inventory</div>
          </div>
          <?php $active = (string)($active_page ?? ''); ?>
          <nav class="px-3 py-4 space-y-1.5 text-sm">
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='home' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=home">Home</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='add' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=add">Product</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='record' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=record">Record</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='statement' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=statement">Statement</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='transaction' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=transaction">Transaction</a>
            <div class="h-px bg-slate-800 my-3"></div>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='account' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=account">Account</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='help' ? 'bg-indigo-500 text-white shadow' : 'text-slate-300 hover:bg-slate-900 hover:text-white' ?>" href="/index.php?page=help">Help</a>
          </nav>
          <div class="px-4 py-4 border-t border-slate-800 mt-auto">
            <form method="post" action="/logout.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
              <button class="w-full text-sm px-3 py-2.5 rounded-lg border border-slate-700 text-slate-100 bg-slate-900 hover:bg-slate-800 transition" type="submit">Logout</button>
            </form>
          </div>
        </aside>
        <main class="flex-1">
          <div class="max-w-6xl mx-auto px-3 py-4 md:px-6 md:py-7">
            <?php if (!empty($flash)) : ?>
              <div class="mb-5 p-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 shadow-sm"><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
          </div>
        </main>
      </div>
      <?php else: ?>
        <main class="max-w-6xl mx-auto px-3 py-4 md:px-6 md:py-7">
          <?= $content ?? '' ?>
        </main>
      <?php endif; ?>
    </div>
  </body>
</html>

