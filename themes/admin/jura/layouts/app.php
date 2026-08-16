<?php
use App\Core\View;
use App\Core\ModuleLoader;
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$nav = array_merge(
  ['Основне' => ['/admin'=>'Дашборд','/admin/settings'=>'Налаштування','/admin/pages'=>'Сторінки','/admin/posts'=>'Публікації','/admin/menus'=>'Меню','/admin/media'=>'Медіа','/admin/redirects'=>'Редіректи']],
  ModuleLoader::getAdminNav(),
  ['Система' => ['/admin/users'=>'Користувачі','/admin/modules'=>'Модулі','/admin/themes'=>'Шаблони','/admin/maintenance'=>'Обслуговування','/admin/updates'=>'Оновлення']]
);
global $pdo;
$_juraTheme = 'indigo';
$_darkMode  = '';
$_adminEmail = '';
if (isset($pdo)) {
  try {
    $_s = cms_settings($pdo);
    $_juraTheme  = $_s['admin_jura_theme'] ?? 'indigo';
    $_darkMode   = ($_s['admin_dark_mode'] ?? '') === '1' ? 'dark' : '';
    $_adminEmail = $_SESSION['admin_user_email'] ?? '';
  } catch (\Throwable $e) {}
}
$_themeAttr = ($_juraTheme && $_juraTheme !== 'indigo') ? ' data-jura-theme="' . e($_juraTheme) . '"' : '';
$_darkAttr  = $_darkMode ? ' data-theme="dark"' : '';
$_adminInitial = $_adminEmail ? strtoupper(substr($_adminEmail, 0, 1)) : 'A';
?>
<!doctype html>
<html lang="uk"<?= $_themeAttr . $_darkAttr ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Jura CMS') ?> — Jura CMS</title>
  <?php foreach ($assets['css'] ?? [] as $css): ?>
  <link rel="stylesheet" href="<?= e(asset_url($css)) ?>">
  <?php endforeach; ?>
</head>
<body class="jura-admin">
<div class="jura-layout">
  <aside class="jura-sidebar">
    <div class="jura-sidebar-brand">
      ✦ Jura CMS
    </div>
    <?php foreach ($nav as $group => $navLinks): ?>
    <p class="jura-nav-group"><?= e($group) ?></p>
    <nav>
      <?php foreach ($navLinks as $url => $label): ?>
      <a href="<?= e($url) ?>"<?= ($current === $url || ($url !== '/admin' && str_starts_with($current, $url))) ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endforeach; ?>
  </aside>
  <main class="jura-main">
    <header class="jura-topbar">
      <h1 class="jura-topbar-title"><?= e($title ?? '') ?></h1>
      <div class="jura-topbar-actions">
        <a class="jura-btn jura-btn-secondary jura-btn-sm" href="/" target="_blank" rel="noopener">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Сайт
        </a>
        <!-- User menu -->
        <div style="position:relative">
          <button id="user-menu-btn" type="button"
            style="width:34px;height:34px;border-radius:50%;background:var(--jura-primary,#4f46e5);color:#fff;border:none;cursor:pointer;font-weight:700;font-size:.875rem;display:flex;align-items:center;justify-content:center"
            aria-label="Меню користувача">
            <?= e($_adminInitial) ?>
          </button>
          <div id="user-menu-drop" hidden
            style="position:absolute;right:0;top:calc(100% + 6px);background:var(--jura-surface,#fff);border:1px solid var(--jura-border,#e2e8f0);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);min-width:180px;z-index:200;padding:6px">
            <?php if ($_adminEmail): ?>
            <div style="padding:8px 12px 6px;font-size:.8rem;color:var(--jura-text-muted,#64748b);border-bottom:1px solid var(--jura-border,#e2e8f0);margin-bottom:4px"><?= e($_adminEmail) ?></div>
            <?php endif; ?>
            <a href="/admin/settings" style="display:block;padding:8px 12px;border-radius:6px;font-size:.875rem;color:var(--jura-text,#1e293b);text-decoration:none" onmouseover="this.style.background='var(--jura-surface-muted,#f1f5f9)'" onmouseout="this.style.background=''"
            >⚙ Налаштування</a>
            <form method="post" action="/admin/logout" style="margin:0">
              <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
              <button type="submit" style="width:100%;text-align:left;padding:8px 12px;border:none;background:none;cursor:pointer;border-radius:6px;font-size:.875rem;color:#e11d48"
                onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background=''">
                ↩ Вийти
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>
    <section class="jura-content">
      <?php include $viewFile; ?>
    </section>
  </main>
</div>
<?php foreach ($assets['js'] ?? [] as $js): ?>
<script src="<?= e(asset_url($js)) ?>"></script>
<?php endforeach; ?>
<script>
(function(){
  var btn  = document.getElementById('user-menu-btn');
  var drop = document.getElementById('user-menu-drop');
  if (!btn || !drop) return;
  btn.addEventListener('click', function(e){ e.stopPropagation(); drop.hidden = !drop.hidden; });
  document.addEventListener('click', function(){ drop.hidden = true; });
})();
</script>
</body>
</html>
