<?php
$dbLabels = [
    'host' => 'Хост базы данных',
    'port' => 'Порт',
    'database' => 'Имя базы данных',
    'username' => 'Пользователь БД',
    'password' => 'Пароль БД',
    'prefix' => 'Префикс таблиц',
];
$dbPlaceholders = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'my_database',
    'username' => 'db_user',
    'password' => '',
    'prefix' => 'jura_',
];
$dbTypes = ['password' => 'password'];
$stepLabels = ['Окружение', 'База данных', 'Пользователь', 'Завершение'];
$stepTitles = [
    1 => ['Проверим окружение', 'Убедимся, что сервер готов к установке Jura CMS.'],
    2 => ['Подключение к базе данных', 'Данные для доступа к MySQL/MariaDB.'],
    3 => ['Учётная запись администратора', 'Понадобится для входа в панель управления.'],
    4 => ['Всё готово к установке', 'Проверьте параметры и запускайте.'],
];
[$stepTitle, $stepSubtitle] = $stepTitles[$step] ?? $stepTitles[1];
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка Jura CMS</title>
<link rel="stylesheet" href="/install/assets/vendor/jura-ui/jura-ui.css">
<style>
  body { margin: 0; min-height: 100vh; background: var(--jura-bg); }
  .install-layout { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }
  .install-brand {
    display: flex; flex-direction: column; justify-content: center;
    padding: 3rem; color: #fff; position: relative; overflow: hidden;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #22d3ee 100%);
  }
  .install-brand::before {
    content: ''; position: absolute; top: -35%; right: -15%;
    width: 480px; height: 480px; border-radius: 50%;
    background: rgba(255,255,255,.06); pointer-events: none;
  }
  .install-brand::after {
    content: ''; position: absolute; bottom: -30%; left: -10%;
    width: 380px; height: 380px; border-radius: 50%;
    background: rgba(255,255,255,.05); pointer-events: none;
  }
  .install-brand-inner { position: relative; z-index: 1; max-width: 420px; }
  .install-form-side { display: flex; align-items: center; justify-content: center; padding: 3rem 2rem; }
  .install-form-wrap { width: 100%; max-width: 460px; }
  .install-feature { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: .9rem; }
  .install-feature-tick {
    width: 1.3rem; height: 1.3rem; border-radius: 50%; background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: .1rem;
  }
  .field-label { font-size: .82rem; font-weight: 600; color: var(--jura-text); display: block; margin-bottom: .4rem; }
  .check-list { list-style: none; padding: 0; margin: 0; display: grid; gap: .5rem; }
  .check-list li { display: flex; align-items: center; justify-content: space-between; padding: .6rem .85rem; border: 1px solid var(--jura-border); border-radius: var(--jura-radius-sm); font-size: .88rem; }
  .spinner { display: none; align-items: center; gap: .5rem; margin-top: 1rem; color: var(--jura-text-muted); font-size: .88rem; }
  .spinner.on { display: flex; }
  .spinner .dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--jura-border); border-top-color: var(--jura-primary); animation: spin .8s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  button[disabled] { opacity: .6; cursor: default; }
  @media (max-width: 860px) {
    .install-layout { grid-template-columns: 1fr; }
    .install-brand { display: none; }
    .install-form-side { padding: 2rem 1.25rem; }
  }
</style>
</head>
<body class="jura-app">
<div class="install-layout">

  <div class="install-brand">
    <div class="install-brand-inner">
      <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:2.5rem">
        <img src="/install/assets/logo.svg" width="40" height="40" alt="">
        <span style="font-size:1.2rem;font-weight:800;letter-spacing:-.02em">Jura CMS</span>
      </div>
      <h1 style="font-size:1.9rem;font-weight:800;line-height:1.25;margin:0 0 .9rem;letter-spacing:-.03em">
        Класична установка,<br>сучасна адмін-панель.
      </h1>
      <p style="font-size:.98rem;opacity:.85;line-height:1.6;margin:0 0 2.2rem">
        Jura CMS — легка система керування сайтом: сторінки, блог, медіатека, меню та мультимовність із коробки.
      </p>
      <div>
        <div class="install-feature">
          <div class="install-feature-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div><strong style="font-size:.9rem">Встановлення за кілька хвилин</strong><br><span style="font-size:.82rem;opacity:.8">Без консолі — весь процес через браузер.</span></div>
        </div>
        <div class="install-feature">
          <div class="install-feature-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div><strong style="font-size:.9rem">Мультимовність із коробки</strong><br><span style="font-size:.82rem;opacity:.8">Переклади сторінок, публікацій і модулів.</span></div>
        </div>
        <div class="install-feature">
          <div class="install-feature-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div><strong style="font-size:.9rem">Модулі під ваш проєкт</strong><br><span style="font-size:.82rem;opacity:.8">Портфоліо, Hotel, Git Deploy — вмикаються за потреби.</span></div>
        </div>
        <div class="install-feature">
          <div class="install-feature-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div><strong style="font-size:.9rem">Оновлення в один клік</strong><br><span style="font-size:.82rem;opacity:.8">Прямо з адмін-панелі, без ручного копіювання файлів.</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="install-form-side">
    <div class="install-form-wrap">
      <div style="margin-bottom:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
          <span style="font-size:.8rem;font-weight:600;color:var(--jura-text-muted)">Крок <?= (int) $step ?> із 4 · <?= e($stepLabels[$step - 1] ?? '') ?></span>
          <span style="font-size:.8rem;color:var(--jura-text-subtle)"><?= (int) round($step / 4 * 100) ?>%</span>
        </div>
        <div class="jura-progress"><div class="jura-progress-bar" style="width:<?= (int) round($step / 4 * 100) ?>%"></div></div>
      </div>

      <h2 style="font-size:1.4rem;font-weight:800;color:var(--jura-text);letter-spacing:-.02em;margin:0 0 .3rem"><?= e($stepTitle) ?></h2>
      <p style="color:var(--jura-text-muted);font-size:.9rem;margin:0 0 1.5rem"><?= e($stepSubtitle) ?></p>

      <?php foreach ($errors as $err): ?>
      <div class="jura-alert jura-alert-danger" style="margin-bottom:1rem"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" id="install-form">
        <?php foreach (['host', 'port', 'database', 'username', 'password', 'prefix'] as $f): ?>
        <input type="hidden" name="carry_db_<?= $f ?>" value="<?= e($state['db'][$f] ?? '') ?>">
        <?php endforeach; ?>
        <?php foreach (['site_name', 'admin_name', 'admin_email'] as $f): ?>
        <input type="hidden" name="carry_admin_<?= $f ?>" value="<?= e($state['admin'][$f] ?? '') ?>">
        <?php endforeach; ?>
        <input type="hidden" name="carry_admin_password_hash" value="<?= e($state['admin_password_hash'] ?? '') ?>">

        <?php if ($step === 1): ?>
          <ul class="check-list" style="margin-bottom:1.5rem">
          <?php foreach ($checks as $k => $ok): ?>
            <li>
              <span><?= e($k) ?></span>
              <?php if ($ok): ?>
              <span class="jura-badge jura-badge-success">OK</span>
              <?php else: ?>
              <span class="jura-badge jura-badge-danger">FAIL</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          </ul>
          <input type="hidden" name="install_action" value="environment-next">
          <button class="jura-btn jura-btn-primary jura-btn-lg" style="width:100%;justify-content:center" type="submit">Далее</button>

        <?php elseif ($step === 2): ?>
          <input type="hidden" name="install_action" value="db-next">
          <div style="display:flex;flex-direction:column;gap:1rem">
          <?php foreach (['host', 'port', 'database', 'username', 'password', 'prefix'] as $f): ?>
            <div class="jura-form-group" style="margin:0">
              <label class="field-label"><?= e($dbLabels[$f]) ?></label>
              <input class="jura-input" type="<?= $dbTypes[$f] ?? 'text' ?>" name="db_<?= $f ?>" value="<?= e($state['db'][$f] ?? '') ?>" placeholder="<?= e($dbPlaceholders[$f] ?? '') ?>">
            </div>
          <?php endforeach; ?>
          </div>
          <button class="jura-btn jura-btn-primary jura-btn-lg" style="width:100%;justify-content:center;margin-top:1.4rem" type="submit">Проверить БД</button>
          <div class="spinner" data-spinner-text="Проверяем подключение к базе данных…"><span class="dot"></span><span class="spinner-text"></span></div>

        <?php elseif ($step === 3): ?>
          <input type="hidden" name="install_action" value="admin-next">
          <div style="display:flex;flex-direction:column;gap:1rem">
            <div class="jura-form-group" style="margin:0">
              <label class="field-label">Название сайта</label>
              <input class="jura-input" name="site_name" value="<?= e($state['admin']['site_name']) ?>">
            </div>
            <div class="jura-form-group" style="margin:0">
              <label class="field-label">Имя администратора</label>
              <input class="jura-input" name="admin_name" value="<?= e($state['admin']['admin_name']) ?>">
            </div>
            <div class="jura-form-group" style="margin:0">
              <label class="field-label">Email администратора</label>
              <input class="jura-input" type="email" name="admin_email" value="<?= e($state['admin']['admin_email']) ?>" placeholder="admin@example.com">
            </div>
            <div class="jura-form-group" style="margin:0">
              <label class="field-label">Пароль</label>
              <input class="jura-input" type="password" name="admin_password" placeholder="минимум 8 символов">
            </div>
            <div class="jura-form-group" style="margin:0">
              <label class="field-label">Подтверждение пароля</label>
              <input class="jura-input" type="password" name="admin_password_confirmation">
            </div>
          </div>
          <button class="jura-btn jura-btn-primary jura-btn-lg" style="width:100%;justify-content:center;margin-top:1.4rem" type="submit">Далее</button>

        <?php else: ?>
          <input type="hidden" name="install_action" value="install-run">
          <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:1.4rem">
            <label class="jura-checkbox" style="align-items:flex-start">
              <input type="checkbox" name="install_demo_data" value="1" checked>
              <span style="font-size:.88rem;color:var(--jura-text)">
                <strong>Установить демо-данные.</strong>
                <span style="display:block;color:var(--jura-text-muted);font-size:.83rem;margin-top:.15rem">Добавит несколько примеров записей в блог. Снимите галочку для чистой установки.</span>
              </span>
            </label>
            <label class="jura-checkbox" style="align-items:flex-start">
              <input type="checkbox" name="clean_install" value="1">
              <span style="font-size:.88rem;color:var(--jura-text)">
                <strong>Очистить базу данных перед установкой.</strong>
                <span style="display:block;color:var(--jura-text-muted);font-size:.83rem;margin-top:.15rem">Удалит все существующие таблицы Jura CMS (jura_*) в выбранной базе. Действие необратимо.</span>
              </span>
            </label>
          </div>
          <button class="jura-btn jura-btn-primary jura-btn-lg" style="width:100%;justify-content:center" type="submit" id="install-run-btn">Установить Jura CMS</button>
          <div class="spinner" data-spinner-text="Устанавливаем Jura CMS… это может занять до минуты, не закрывайте страницу"><span class="dot"></span><span class="spinner-text"></span></div>
        <?php endif; ?>
      </form>
    </div>
  </div>

</div>
<script>
(function () {
  var form = document.getElementById('install-form');
  if (!form) return;
  form.addEventListener('submit', function () {
    var btn = form.querySelector('button[type="submit"]');
    var spinner = form.querySelector('.spinner');
    if (btn) { btn.disabled = true; }
    if (spinner) {
      var textEl = spinner.querySelector('.spinner-text');
      if (textEl) { textEl.textContent = spinner.getAttribute('data-spinner-text') || 'Пожалуйста, подождите…'; }
      spinner.classList.add('on');
    }
  });
})();
</script>
</body>
</html>
