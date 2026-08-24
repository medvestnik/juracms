<?php if (!empty($shell_disabled)): ?>
<section class="jura-card">
  <div class="jura-alert" style="background:#fff1f2;border-color:#fecdd3;color:#9f1239">
    Функція shell_exec вимкнена на цьому хостингу — модуль Git Deploy недоступний. Зверніться до хостинг-провайдера або оберіть інший тариф із дозволеним shell_exec.
  </div>
</section>
<?php return; endif; ?>

<?php
$isRepo = $is_repo ?? false;
$info = $info ?? null;
$status = $status ?? [];
$logs = $logs ?? [];
$settings = $settings ?? [];
$configInfo = $config_info ?? [];
?>

<?php if (!empty($flash_success)): ?>
<div class="jura-alert" style="margin-bottom:1rem;white-space:pre-line"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239;white-space:pre-line"><?= e($flash_error) ?></div>
<?php endif; ?>
<?php if (!empty($flash_ssh_key)): ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Публічний SSH-ключ</h2>
  <p style="color:#64748b;font-size:.88rem">Додайте цей ключ у GitHub → Settings → Deploy keys (репозиторію) з правом запису, потім оберіть автентифікацію «SSH-ключ» нижче.</p>
  <textarea class="jura-input" readonly onclick="this.select()" rows="3" style="font-family:monospace;font-size:.8rem"><?= e($flash_ssh_key) ?></textarea>
</section>
<?php endif; ?>
<?php if (!empty($flash_output)): ?>
<details class="jura-card" style="margin-bottom:1rem" open>
  <summary style="cursor:pointer;font-weight:700">Вивід останньої операції</summary>
  <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;max-height:360px;font-size:.78rem;line-height:1.5;white-space:pre-wrap;margin-top:.75rem"><?= e($flash_output) ?></pre>
</details>
<?php endif; ?>

<?php if (!$isRepo): ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Підключити репозиторій</h2>
  <p style="color:#64748b;font-size:.88rem">
    Підключіть цей сайт до git-репозиторію (наприклад, на GitHub), щоб виконувати git pull / commit &amp; push прямо з адмінки —
    зручно, якщо код сайту редагує AI-агент (наприклад, Claude Code) або ви самі працюєте через git.
  </p>
  <form method="post" action="/admin/gitdeploy/init">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Адреса репозиторію</label>
        <input class="jura-input" name="remote_url" placeholder="git@github.com:owner/repo.git" required>
      </div>
      <div>
        <label class="jura-label">Гілка</label>
        <input class="jura-input" name="branch" value="main">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Автентифікація</label>
      <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.4rem">
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="none" checked onchange="gdToggleAuth(this)"> Без автентифікації (публічний репозиторій)</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="https_token" onchange="gdToggleAuth(this)"> HTTPS-токен</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="ssh_key" onchange="gdToggleAuth(this)"> SSH-ключ</label>
      </div>
    </div>
    <div id="gd-auth-token" style="display:none;margin-top:.75rem">
      <label class="jura-label">Personal Access Token (GitHub)</label>
      <input class="jura-input" type="password" name="token" placeholder="ghp_...">
    </div>
    <div id="gd-auth-ssh" style="display:none;margin-top:.75rem">
      <label class="jura-label">Приватний SSH-ключ (залиште порожнім, якщо вже згенеровано нижче)</label>
      <textarea class="jura-input" name="ssh_key" rows="4" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----" style="font-family:monospace;font-size:.8rem"></textarea>
      <button class="jura-btn jura-btn-secondary" type="submit" formaction="/admin/gitdeploy/generate-ssh-key" style="margin-top:.5rem">Згенерувати SSH-ключ на сервері</button>
    </div>
    <div style="margin-top:1rem">
      <label style="display:flex;align-items:center;gap:.5rem"><input type="checkbox" name="create_gitignore" value="1" checked> Створити типовий .gitignore (config.php, storage/, uploads/, cache/, logs/)</label>
    </div>
    <button class="jura-btn jura-btn-primary" type="submit" style="margin-top:1.2rem">Підключити</button>
  </form>
  <script>
  function gdToggleAuth(radio) {
    document.getElementById('gd-auth-token').style.display = radio.value === 'https_token' ? 'block' : 'none';
    document.getElementById('gd-auth-ssh').style.display = radio.value === 'ssh_key' ? 'block' : 'none';
  }
  </script>
</section>
<?php else: ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Стан підключення</h2>
  <table style="width:100%;border-collapse:collapse;font-size:.9rem;margin-bottom:1rem">
    <tr><td style="padding:.3rem 0;color:#94a3b8;width:160px">Гілка</td><td><?= e($info['branch'] ?? '—') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Останній коміт</td><td><?= e($info['commit'] ?? '—') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Remote</td><td><code><?= e($info['remote'] ?? '—') ?></code></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Позаду origin</td><td><?= (int) ($info['behind'] ?? 0) ?> комітів</td></tr>
  </table>
  <form method="post" action="/admin/gitdeploy/pull" style="display:inline">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <button class="jura-btn jura-btn-primary" type="submit">⬇ git pull</button>
  </form>
</section>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Зміни у файлах (<?= count($status) ?>)</h2>
  <?php if (empty($status)): ?>
  <p style="color:#888">Немає незакомічених змін.</p>
  <?php else: ?>
  <form method="post" action="/admin/gitdeploy/commit" id="gd-commit-form">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <table class="jura-table" style="margin-bottom:1rem">
      <thead><tr><th style="width:24px"><input type="checkbox" onclick="document.querySelectorAll('.gd-file-cb').forEach(c=>c.checked=this.checked)"></th><th>Файл</th><th>Статус</th></tr></thead>
      <tbody>
      <?php foreach ($status as $f): ?>
        <tr>
          <td><input class="gd-file-cb" type="checkbox" name="files[]" value="<?= e($f['file']) ?>"></td>
          <td><a href="#" onclick="gdShowDiff(event,<?= json_encode($f['file']) ?>)"><?= e($f['file']) ?></a></td>
          <td style="font-size:.8rem;color:#64748b"><?= e($f['label']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div style="display:flex;gap:.6rem;align-items:flex-start;flex-wrap:wrap">
      <input class="jura-input" name="message" placeholder="Повідомлення коміту" style="flex:1;min-width:240px;margin:0" required>
      <button class="jura-btn jura-btn-primary" type="submit">Commit &amp; push</button>
      <button class="jura-btn jura-btn-secondary" type="submit" formaction="/admin/gitdeploy/gitignore" formnovalidate>Додати до .gitignore</button>
    </div>
  </form>
  <div id="gd-diff-box" style="display:none;margin-top:1rem">
    <h3 id="gd-diff-title" style="font-size:.9rem"></h3>
    <pre id="gd-diff-content" style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;max-height:400px;font-size:.78rem;line-height:1.5;white-space:pre-wrap"></pre>
  </div>
  <script>
  function gdShowDiff(e, file) {
    e.preventDefault();
    fetch('/admin/gitdeploy/diff?file=' + encodeURIComponent(file))
      .then(function(r){ return r.json(); })
      .then(function(d){
        document.getElementById('gd-diff-title').textContent = file;
        document.getElementById('gd-diff-content').textContent = d.diff || '';
        document.getElementById('gd-diff-box').style.display = 'block';
      });
  }
  </script>
  <?php endif; ?>
</section>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Для AI-агента, що працює з цим репозиторієм</h2>
  <p style="color:#64748b;font-size:.88rem">Схема БД та інформація про конфігурацію сайту (без паролів) — щоб агент розумів контекст, не маючи доступу до config.php.</p>
  <form method="post" action="/admin/gitdeploy/save-schema" style="margin-bottom:1rem">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <button class="jura-btn jura-btn-secondary" type="submit">Зберегти схему БД у db_schema.md</button>
  </form>
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <?php foreach ($configInfo as $key => $value): ?>
    <tr><td style="padding:.25rem 0;color:#94a3b8;width:160px"><?= e($key) ?></td><td><code><?= e((string) $value) ?></code></td></tr>
    <?php endforeach; ?>
  </table>
</section>

<details class="jura-card" style="margin-bottom:1rem">
  <summary style="cursor:pointer;font-weight:700">🔑 Змінити адресу репозиторію / спосіб автентифікації</summary>
  <div class="jura-alert" style="margin-top:1rem">
    Репозиторій вже підключено — форма нижче лише оновлює remote-адресу та/або спосіб автентифікації, файли сайту не чіпаються. Використайте це, якщо <strong>git pull</strong> падає з помилкою на кшталт <code>could not read Username for 'https://github.com'</code> — це означає, що для приватного репозиторію ще не налаштована автентифікація.
  </div>
  <form method="post" action="/admin/gitdeploy/init" style="margin-top:1rem">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Адреса репозиторію</label>
        <input class="jura-input" name="remote_url" value="<?= e($info['remote'] ?? '') ?>" required>
      </div>
      <div>
        <label class="jura-label">Гілка</label>
        <input class="jura-input" name="branch" value="<?= e($info['branch'] ?? 'main') ?>">
      </div>
    </div>
    <?php $curAuth = $settings['gitdeploy_auth_type'] ?? 'none'; ?>
    <div style="margin-top:1rem">
      <label class="jura-label">Автентифікація</label>
      <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.4rem">
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="none" <?= $curAuth === 'none' ? 'checked' : '' ?> onchange="gdToggleAuthReconnect(this)"> Без автентифікації (публічний репозиторій)</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="https_token" <?= $curAuth === 'https_token' ? 'checked' : '' ?> onchange="gdToggleAuthReconnect(this)"> HTTPS-токен</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="ssh_key" <?= $curAuth === 'ssh_key' ? 'checked' : '' ?> onchange="gdToggleAuthReconnect(this)"> SSH-ключ</label>
      </div>
    </div>
    <div id="gd-auth-token-reconnect" style="display:<?= $curAuth === 'https_token' ? 'block' : 'none' ?>;margin-top:.75rem">
      <label class="jura-label">Personal Access Token (GitHub)</label>
      <input class="jura-input" type="password" name="token" placeholder="ghp_...">
      <p style="color:#64748b;font-size:.8rem;margin:.35rem 0 0">Залиште порожнім, щоб зберегти раніше збережений токен без змін.</p>
    </div>
    <div id="gd-auth-ssh-reconnect" style="display:<?= $curAuth === 'ssh_key' ? 'block' : 'none' ?>;margin-top:.75rem">
      <label class="jura-label">Приватний SSH-ключ (залиште порожнім, якщо вже згенеровано раніше)</label>
      <textarea class="jura-input" name="ssh_key" rows="4" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----" style="font-family:monospace;font-size:.8rem"></textarea>
      <button class="jura-btn jura-btn-secondary" type="submit" formaction="/admin/gitdeploy/generate-ssh-key" style="margin-top:.5rem">Згенерувати SSH-ключ на сервері</button>
    </div>
    <button class="jura-btn jura-btn-primary" type="submit" style="margin-top:1.2rem">Оновити підключення</button>
  </form>
  <script>
  function gdToggleAuthReconnect(radio) {
    document.getElementById('gd-auth-token-reconnect').style.display = radio.value === 'https_token' ? 'block' : 'none';
    document.getElementById('gd-auth-ssh-reconnect').style.display = radio.value === 'ssh_key' ? 'block' : 'none';
  }
  </script>
</details>

<details class="jura-card" style="margin-bottom:1rem">
  <summary style="cursor:pointer;font-weight:700">Налаштування</summary>
  <form method="post" action="/admin/gitdeploy/init" style="margin-top:1rem">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Ім'я автора комітів</label>
        <input class="jura-input" name="author_name" value="<?= e($settings['gitdeploy_author_name'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Email автора комітів</label>
        <input class="jura-input" name="author_email" value="<?= e($settings['gitdeploy_author_email'] ?? '') ?>">
      </div>
    </div>
    <input type="hidden" name="remote_url" value="<?= e($info['remote'] ?? '') ?>">
    <input type="hidden" name="auth_type" value="<?= e($settings['gitdeploy_auth_type'] ?? 'none') ?>">
    <button class="jura-btn jura-btn-secondary" type="submit" style="margin-top:1rem">Зберегти</button>
  </form>
</details>

<details class="jura-card">
  <summary style="cursor:pointer;font-weight:700;color:#9f1239">Небезпечна зона: скинути репозиторій</summary>
  <p style="color:#64748b;font-size:.85rem;margin:.75rem 0">
    Видаляє локальну історію git (.git) і починає заново з тим самим remote — корисно, якщо репозиторій потрапив у зіпсований стан. Файли сайту не видаляються.
  </p>
  <form method="post" action="/admin/gitdeploy/reset">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <button class="jura-btn" type="submit" style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3" onclick="return confirm('Скинути git-репозиторій? Локальна історія комітів буде втрачена.')">Скинути репозиторій</button>
  </form>
</details>

<?php endif; ?>

<section class="jura-card" style="margin-top:1rem">
  <h2 style="margin-top:0">Останні операції</h2>
  <?php if (empty($logs)): ?>
  <p style="color:#888">Ще не виконано жодної операції.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Тип</th><th>Опис</th><th>Час</th><th>Результат</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= e($l['type']) ?></td>
        <td><?= e($l['name']) ?></td>
        <td style="font-size:.82rem;color:#64748b"><?= e($l['executed_at']) ?></td>
        <td><?= ((int) $l['success']) === 1 ? '✓' : '✕' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
