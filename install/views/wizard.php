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
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Jura CMS Installer</title>
<link rel="stylesheet" href="/install/assets/installer.css">
<style>
.spinner{display:none;align-items:center;gap:.5rem;margin-top:1rem;color:#555;font-size:.9rem}
.spinner.on{display:flex}
.spinner .dot{width:16px;height:16px;border-radius:50%;border:2px solid #d0d8e4;border-top-color:#2563eb;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
button[disabled]{opacity:.6;cursor:default}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>Jura CMS</h1>
<p>Шаг <?= (int) $step ?> из 4</p>
<ol class="steps">
  <li class="<?= $step >= 1 ? 'on' : '' ?>">Окружение</li>
  <li class="<?= $step >= 2 ? 'on' : '' ?>">База данных</li>
  <li class="<?= $step >= 3 ? 'on' : '' ?>">Пользователь</li>
  <li class="<?= $step >= 4 ? 'on' : '' ?>">Завершение</li>
</ol>

<?php foreach ($errors as $e): ?>
<div class="err"><?= e($e) ?></div>
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
    <ul>
    <?php foreach ($checks as $k => $ok): ?>
      <li><?= e($k) ?>: <b class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FAIL' ?></b></li>
    <?php endforeach; ?>
    </ul>
    <input type="hidden" name="install_action" value="environment-next">
    <button type="submit">Далее</button>

  <?php elseif ($step === 2): ?>
    <input type="hidden" name="install_action" value="db-next">
    <?php foreach (['host', 'port', 'database', 'username', 'password', 'prefix'] as $f): ?>
    <label><?= e($dbLabels[$f]) ?>
      <input type="<?= $dbTypes[$f] ?? 'text' ?>" name="db_<?= $f ?>" value="<?= e($state['db'][$f] ?? '') ?>" placeholder="<?= e($dbPlaceholders[$f] ?? '') ?>">
    </label>
    <?php endforeach; ?>
    <button type="submit">Проверить БД</button>
    <div class="spinner" data-spinner-text="Проверяем подключение к базе данных…"><span class="dot"></span><span class="spinner-text"></span></div>

  <?php elseif ($step === 3): ?>
    <input type="hidden" name="install_action" value="admin-next">
    <label>Название сайта
      <input name="site_name" value="<?= e($state['admin']['site_name']) ?>">
    </label>
    <label>Имя администратора
      <input name="admin_name" value="<?= e($state['admin']['admin_name']) ?>">
    </label>
    <label>Email администратора
      <input type="email" name="admin_email" value="<?= e($state['admin']['admin_email']) ?>" placeholder="admin@example.com">
    </label>
    <label>Пароль
      <input type="password" name="admin_password" placeholder="минимум 8 символов">
    </label>
    <label>Подтверждение пароля
      <input type="password" name="admin_password_confirmation">
    </label>
    <button type="submit">Далее</button>

  <?php else: ?>
    <input type="hidden" name="install_action" value="install-run">
    <p>Готово к установке полного CMS-скелета.</p>
    <label style="display:flex;align-items:flex-start;gap:.5rem;margin:1rem 0">
      <input type="checkbox" name="install_demo_data" value="1" checked style="width:auto;flex:0 0 auto;margin-top:.2rem">
      <span style="display:block">
        <strong>Установить демо-данные.</strong><br>
        Добавит несколько примеров записей в блог, чтобы сразу увидеть, как выглядит сайт с контентом. Снимите галочку для чистой установки без примеров.
      </span>
    </label>
    <label style="display:flex;align-items:flex-start;gap:.5rem;margin:1rem 0">
      <input type="checkbox" name="clean_install" value="1" style="width:auto;flex:0 0 auto;margin-top:.2rem">
      <span style="display:block">
        <strong>Очистить базу данных перед установкой.</strong><br>
        Удалит все существующие таблицы Jura CMS (jura_*) и их данные в выбранной базе. Используйте, если база уже содержит установку, от которой нужно избавиться. Это действие необратимо.
      </span>
    </label>
    <button type="submit" id="install-run-btn">Установить Jura CMS</button>
    <div class="spinner" data-spinner-text="Устанавливаем Jura CMS… это может занять до минуты, не закрывайте страницу"><span class="dot"></span><span class="spinner-text"></span></div>
  <?php endif; ?>
</form>
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
