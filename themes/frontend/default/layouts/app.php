<?php
global $pdo;
$settings = $settings ?? (isset($pdo) ? cms_settings($pdo) : []);
$siteName = $settings['site_name'] ?? 'Jura CMS';
$currentLocaleCode = $locale ?? ($settings['default_locale'] ?? 'uk');
$headerItems = isset($pdo) ? frontend_menu_items($pdo, $settings['menu_header'] ?? 'main', $currentLocaleCode) : [];
$footerItems = isset($pdo) ? frontend_menu_items($pdo, $settings['menu_footer'] ?? 'main', $currentLocaleCode) : [];
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$metaDescription = $meta_description ?? '';

// Language switcher: only shown once more than one locale is active.
// Uses the current view's translation map when available (so it links to
// the actual translated page/post), falling back to that locale's home
// page for views that don't carry $translations (e.g. 404, module pages).
$siteLocales = [];
if (isset($pdo)) {
    try {
        $localeRows = $pdo->query('SELECT code,native_name FROM ' . jura_table('locales') . ' WHERE is_active=1 ORDER BY sort_order,id')->fetchAll();
    } catch (\Throwable) {
        $localeRows = [];
    }
    if (count($localeRows) > 1) {
        [, $defaultLocaleCode] = active_locales($pdo);
        $translationMap = $translations ?? [];
        foreach ($localeRows as $lr) {
            $code = (string) $lr['code'];
            $url = $translationMap[$code]['route_path'] ?? ($code === $defaultLocaleCode ? '/' : '/' . $code);
            $siteLocales[] = ['code' => $code, 'label' => (string) $lr['native_name'], 'url' => $url, 'current' => $code === $currentLocaleCode];
        }
    }
}
?>
<!doctype html>
<html lang="<?= e($currentLocaleCode) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $siteName) ?><?= ($title ?? '') !== $siteName ? ' — ' . e($siteName) : '' ?></title>
  <?php if ($metaDescription !== ''): ?>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <?php endif; ?>
  <meta property="og:title" content="<?= e($title ?? $siteName) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <?php foreach ($assets['css'] ?? [] as $css): ?>
  <link rel="stylesheet" href="<?= e(asset_url($css)) ?>">
  <?php endforeach; ?>
  <script>
  (function(){
    try {
      var saved = localStorage.getItem('jura-site-theme');
      if (saved === 'light' || saved === 'dark') {
        document.documentElement.setAttribute('data-theme', saved);
      }
    } catch (e) {}
  })();
  </script>
  <?php $gtmId = trim((string) ($settings['gtm_id'] ?? '')); if ($gtmId !== ''): ?>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtmId) ?>');</script>
  <?php endif; ?>
  <?= \App\Core\ModuleLoader::hookRender('head_scripts', $settings ?? []) ?>
</head>
<body class="site">
<div class="site-wrap">
  <header class="site-header">
    <div class="site-container site-header__bar">
      <a class="site-logo" href="/"><span class="site-logo__mark">J</span><?= e($siteName) ?></a>
      <?php if (!empty($headerItems)): ?>
      <nav class="site-nav" id="site-nav">
        <?php foreach ($headerItems as $item): $url = (string) $item['url']; ?>
        <a href="<?= e($url) ?>" target="<?= e($item['target'] ?? '_self') ?>"<?= ($current === $url || ($url !== '/' && str_starts_with($current, $url))) ? ' class="is-active"' : '' ?>><?= e($item['title']) ?></a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
      <div class="site-header__right">
        <?php if (!empty($siteLocales)): ?>
        <div class="lang-switcher" style="position:relative">
          <button type="button" class="lang-switcher__btn" id="lang-switcher-btn" style="background:none;border:1px solid var(--site-border,#e2e8f0);border-radius:6px;padding:.35rem .6rem;font-size:.85rem;cursor:pointer;text-transform:uppercase" aria-label="Мова">
            <?= e($currentLocaleCode) ?> ▾
          </button>
          <div id="lang-switcher-drop" hidden style="position:absolute;right:0;top:calc(100% + 4px);background:var(--site-surface,#fff);border:1px solid var(--site-border,#e2e8f0);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.1);min-width:140px;z-index:200;padding:4px">
            <?php foreach ($siteLocales as $sl): ?>
            <a href="<?= e($sl['url']) ?>" style="display:block;padding:6px 10px;border-radius:6px;font-size:.85rem;text-decoration:none;color:inherit<?= $sl['current'] ? ';font-weight:700' : '' ?>"><?= e($sl['label']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Перемкнути тему">
          <span class="icon-light">☀️</span><span class="icon-dark">🌙</span>
        </button>
        <button type="button" class="site-nav-toggle" id="site-nav-toggle" aria-label="Меню" aria-expanded="false">☰</button>
      </div>
    </div>
  </header>

  <main>
    <?php include $viewFile; ?>
  </main>

  <footer class="site-footer">
    <div class="site-container">
      <div class="site-footer__top">
        <div class="site-footer__brand"><?= e($siteName) ?></div>
        <?php if (!empty($footerItems)): ?>
        <nav>
          <?php foreach ($footerItems as $item): ?>
          <a href="<?= e($item['url']) ?>" target="<?= e($item['target'] ?? '_self') ?>"><?= e($item['title']) ?></a>
          <?php endforeach; ?>
        </nav>
        <?php endif; ?>
      </div>
      <div class="site-footer__contacts">
        <?php if (!empty($settings['contact_phone']) && ($settings['show_phone'] ?? '1') !== '0'): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $settings['contact_phone'])) ?>"><?= e($settings['contact_phone']) ?></a><?php endif; ?>
        <?php if (!empty($settings['contact_email'])): ?><a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a><?php endif; ?>
        <?php if (!empty($settings['contact_address'])): ?><span><?= e($settings['contact_address']) ?></span><?php endif; ?>
      </div>
      <div class="site-footer__bottom">
        <span>&copy; <?= e(date('Y')) ?> <?= e($siteName) ?></span>
        <span>Створено на <a href="https://juracms.com" target="_blank" rel="noopener" style="color:inherit">Jura CMS</a></span>
      </div>
    </div>
  </footer>
</div>
<?php foreach ($assets['js'] ?? [] as $js): ?>
<script src="<?= e(asset_url($js)) ?>"></script>
<?php endforeach; ?>
<script>
(function(){
  var toggle = document.getElementById('site-nav-toggle');
  var nav = document.getElementById('site-nav');
  if (!toggle || !nav) return;
  toggle.addEventListener('click', function(){
    var open = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
(function(){
  var btn = document.getElementById('lang-switcher-btn');
  var drop = document.getElementById('lang-switcher-drop');
  if (!btn || !drop) return;
  btn.addEventListener('click', function(e){ e.stopPropagation(); drop.hidden = !drop.hidden; });
  document.addEventListener('click', function(){ drop.hidden = true; });
})();
(function(){
  var btn = document.getElementById('theme-toggle');
  if (!btn) return;
  function currentTheme(){
    var attr = document.documentElement.getAttribute('data-theme');
    if (attr === 'light' || attr === 'dark') return attr;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  btn.addEventListener('click', function(){
    var next = currentTheme() === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('jura-site-theme', next); } catch (e) {}
  });
})();
</script>
</body>
</html>
