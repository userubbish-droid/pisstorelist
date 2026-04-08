<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-100 to-slate-200">
      <?php if (!empty($show_nav)) : ?>
      <div class="flex min-h-screen">
        <aside class="w-72 bg-white/95 backdrop-blur border-r border-slate-200 min-h-screen sticky top-0 shadow-sm">
          <div class="px-5 py-5 border-b border-slate-100">
            <a class="font-semibold text-slate-900 tracking-tight" href="/index.php?page=home"><?= htmlspecialchars(APP_NAME) ?></a>
            <div class="text-xs text-slate-500 mt-1">Kitchen inventory</div>
          </div>
          <?php $active = (string)($active_page ?? ''); ?>
          <nav class="px-3 py-4 space-y-1.5 text-sm">
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='home' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=home">Home</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='add' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=add">Add</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='record' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=record">Record</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='statement' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=statement">Statement</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='transaction' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=transaction">Transaction</a>
            <div class="h-px bg-slate-100 my-3"></div>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='account' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=account">Account</a>
            <a class="block px-3 py-2.5 rounded-lg transition <?= $active==='help' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>" href="/index.php?page=help">Help</a>
          </nav>
          <div class="px-4 py-4 border-t border-slate-100 mt-auto">
            <form method="post" action="/logout.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
              <button class="w-full text-sm px-3 py-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition" type="submit">Logout</button>
            </form>
          </div>
        </aside>
        <main class="flex-1">
          <div class="max-w-6xl mx-auto px-6 py-7">
            <?php if (!empty($flash)) : ?>
              <div class="mb-4 p-3 rounded border bg-amber-50 text-amber-800"><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
          </div>
        </main>
      </div>
      <?php else: ?>
        <main class="max-w-6xl mx-auto px-6 py-7">
          <?= $content ?? '' ?>
        </main>
      <?php endif; ?>
    </div>
  </body>
</html>

