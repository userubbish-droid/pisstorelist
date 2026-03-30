<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900">
    <?php if (!empty($show_nav)) : ?>
    <nav class="bg-white border-b">
      <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <a class="font-semibold" href="/index.php?page=items"><?= htmlspecialchars(APP_NAME) ?></a>
          <a class="text-sm text-slate-600 hover:text-slate-900" href="/index.php?page=items">物品</a>
          <a class="text-sm text-slate-600 hover:text-slate-900" href="/index.php?page=movements">流水</a>
        </div>
        <form method="post" action="/logout.php">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
          <button class="text-sm px-3 py-1 rounded border hover:bg-slate-50" type="submit">退出</button>
        </form>
      </div>
    </nav>
    <?php endif; ?>

    <main class="max-w-5xl mx-auto px-4 py-6">
      <?php if (!empty($flash)) : ?>
        <div class="mb-4 p-3 rounded border bg-amber-50 text-amber-800"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>
      <?= $content ?? '' ?>
    </main>
  </body>
</html>

