<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Jura CMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/install/assets/jura-ui/jura-ui.css">
<style>
  body {
    margin: 0; min-height: 100vh; background: var(--jura-bg, #f8fafc); display: grid; place-items: center; padding: 20px;
    font-family: var(--jura-font, Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
  }
  h1, h2, .jura-btn { font-family: 'Manrope', var(--jura-font, Inter, -apple-system, sans-serif); }
  .installed-card { width: 100%; max-width: 460px; }
  .installed-list { list-style: none; padding: 0; margin: 0 0 1.2rem; display: grid; gap: .5rem; }
  .installed-list li { display: flex; align-items: center; justify-content: space-between; padding: .55rem .8rem; border: 1px solid var(--jura-border); border-radius: var(--jura-radius-sm); font-size: .85rem; }
  .installed-list li b { color: var(--jura-text-muted); font-weight: 600; }
  .jura-btn-primary {
    background: linear-gradient(135deg, var(--jura-primary) 0%, #1d4ed8 100%);
    border: none;
    box-shadow: 0 2px 10px var(--jura-primary-ring);
    transition: box-shadow .18s ease, transform .18s ease;
  }
  .jura-btn-primary:hover { box-shadow: 0 8px 22px var(--jura-primary-ring); transform: translateY(-1px); }
  .jura-btn-secondary:hover { transform: translateY(-1px); }
  .brand-cms {
    background: linear-gradient(90deg, #1d4ed8, #22d3ee);
    -webkit-background-clip: text; background-clip: text; color: transparent;
  }
</style>
</head>
<body class="jura-app">
<div class="jura-card installed-card">
  <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.4rem">
    <img src="/install/assets/logo.svg" width="36" height="36" alt="">
    <span style="font-size:1.05rem;font-weight:800;letter-spacing:-.02em">Jura <span class="brand-cms">CMS</span></span>
  </div>

  <?php if (!empty($justInstalled)): ?>
  <div class="jura-alert jura-alert-success" style="margin-bottom:1.2rem">✓ Установка завершена!</div>
  <?php else: ?>
  <h1 style="font-size:1.25rem;font-weight:800;margin:0 0 1.2rem">Jura CMS уже установлена</h1>
  <?php endif; ?>

  <ul class="installed-list">
    <li><b>Название сайта</b> <span><?= e($siteName) ?></span></li>
    <li><b>Версия</b> <span><?= e((string)($config['app']['version'] ?? $version)) ?></span></li>
    <?php if ($adminEmail): ?><li><b>Email администратора</b> <span><?= e((string)$adminEmail) ?></span></li><?php endif; ?>
  </ul>

  <?php if ($usersWarning): ?>
  <div class="jura-alert jura-alert-danger" style="margin-bottom:1.2rem"><?= e($usersWarning) ?></div>
  <?php endif; ?>

  <div style="display:flex;gap:.6rem;margin-bottom:.5rem">
    <a class="jura-btn jura-btn-primary" style="flex:1;justify-content:center" href="/">Перейти на сайт</a>
    <a class="jura-btn jura-btn-secondary" style="flex:1;justify-content:center" href="/admin/login">Открыть админку</a>
  </div>

  <?php if (!$justInstalled): ?>
  <div style="margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--jura-border)">
    <p style="font-size:.85rem;color:var(--jura-text-muted);margin:0 0 .7rem">Хотите переустановить Jura CMS на этом сайте?</p>
    <form method="post" onsubmit="return confirm('Переустановить Jura CMS? Текущая установка будет сброшена (config.php больше не будет отмечен как установленный) — на следующем шаге мастер установки сможет создать таблицы заново или очистить базу. Это действие необратимо.');">
      <input type="hidden" name="install_action" value="reset-installation">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <button class="jura-btn" style="width:100%;justify-content:center;color:var(--jura-danger-fg);border-color:rgba(220,38,38,.25);background:var(--jura-danger-bg)" type="submit">Переустановить</button>
    </form>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
