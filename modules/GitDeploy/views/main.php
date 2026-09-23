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
      <input class="jura-input" id="gd-commit-message" name="message" placeholder="Повідомлення коміту (порожнє поле — заповниться автоматично)" style="flex:1;min-width:240px;margin:0">
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
  (function(){
    var form = document.getElementById('gd-commit-form');
    var msgInput = document.getElementById('gd-commit-message');
    if (!form || !msgInput) return;
    form.addEventListener('submit', function(){
      if (msgInput.value.trim() !== '') return;
      var checked = Array.from(document.querySelectorAll('.gd-file-cb:checked')).map(function(c){ return c.value; });
      if (!checked.length) return;
      var names = checked.map(function(p){ return p.split('/').pop(); });
      var shown = names.slice(0, 3).join(', ');
      var rest = names.length - 3;
      msgInput.value = 'Оновлено: ' + shown + (rest > 0 ? ' та ще ' + rest + ' файл(ів)' : '');
    });
  })();
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

<?php $migrations = $migrations ?? []; $pendingMigrations = array_filter($migrations, fn($m) => !$m['executed']); ?>
<section class="jura-card" id="gd-migrations" style="margin-bottom:1rem">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
    <h2 style="margin:0">Міграції</h2>
    <?php if (!empty($pendingMigrations)): ?>
    <form method="post" action="/admin/gitdeploy/migrations/run-all" style="margin:0">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <button class="jura-btn jura-btn-primary" type="submit">▶ Запустити всі невиконані (<?= count($pendingMigrations) ?>)</button>
    </form>
    <?php endif; ?>
  </div>
  <p style="color:#64748b;font-size:.88rem;margin:0 0 1rem">Файли з <code>migrations/</code> — невеликі PHP-скрипти з прямим доступом до БД (<code>$pdo</code>), для змін схеми або одноразових запитів, без потреби в SSH/консолі БД.</p>
  <?php if (empty($migrations)): ?>
  <p style="color:#888">Файлів міграцій не знайдено в <code>migrations/</code>.</p>
  <?php else: ?>
  <table class="jura-table" style="margin-bottom:0">
    <thead><tr><th>Файл міграції</th><th style="width:140px">Статус</th><th style="width:170px">Виконано</th><th style="width:110px">Дія</th></tr></thead>
    <tbody>
    <?php foreach ($migrations as $m): ?>
      <tr>
        <td>
          <code><?= e($m['name']) ?></code>
          <?php if ($m['is_query']): ?>
          <span style="font-size:.72rem;background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:20px;padding:.1rem .5rem;margin-left:.35rem" title="Запит до БД — після виконання результат треба закомітити">запит до БД</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($m['executed']): ?>
          <span style="font-size:.72rem;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:20px;padding:.1rem .5rem">✓ Виконано</span>
          <?php else: ?>
          <span style="font-size:.72rem;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:.1rem .5rem">Очікує</span>
          <?php endif; ?>
        </td>
        <td style="font-size:.82rem;color:#64748b"><?= e($m['executed_at'] ?? '—') ?></td>
        <td>
          <?php if (!$m['executed']): ?>
          <form method="post" action="/admin/gitdeploy/migrations/run" style="margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="migration" value="<?= e($m['name']) ?>">
            <button class="jura-btn jura-btn-secondary" style="padding:.3rem .6rem;font-size:.8rem" type="submit">▶ Запустити</button>
          </form>
          <?php else: ?>
          <form method="post" action="/admin/gitdeploy/migrations/rerun" style="margin:0" onsubmit="return confirm('Запустити повторно: <?= e($m['name']) ?>?\nЗапис з історії буде видалено, і міграція виконається заново.')">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="migration" value="<?= e($m['name']) ?>">
            <button class="jura-btn jura-btn-secondary" style="padding:.3rem .6rem;font-size:.8rem" type="submit" title="Запустити повторно">↻</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <details style="margin-top:1rem">
    <summary style="cursor:pointer;font-weight:600;font-size:.9rem">Як створити міграцію</summary>
    <div style="margin-top:.75rem;font-size:.85rem;color:#334155">
      <p>Створіть файл у <code>migrations/</code> з іменем формату:</p>
      <pre style="background:#0f172a;color:#e2e8f0;padding:.6rem .8rem;border-radius:8px;font-size:.78rem;overflow:auto">YYYYMMDD_HHMMSS_опис.php</pre>
      <p>Усередині доступні змінні <code>$pdo</code> (PDO-з'єднання з БД сайту) та <code>$repoDir</code> (шлях до кореня репозиторію):</p>
      <pre style="background:#0f172a;color:#e2e8f0;padding:.6rem .8rem;border-radius:8px;font-size:.78rem;overflow:auto">&lt;?php
$pdo->exec("ALTER TABLE " . jura_table('products') . " ADD COLUMN custom_field VARCHAR(255) DEFAULT NULL");
echo "Готово\n";</pre>
      <p><strong>Запити до БД для AI</strong> — якщо потрібно, щоб AI отримав результат SQL-запиту (а не просто змінив схему), назвіть файл із префіксом <code>query_</code> одразу після дати/часу:</p>
      <pre style="background:#0f172a;color:#e2e8f0;padding:.6rem .8rem;border-radius:8px;font-size:.78rem;overflow:auto">YYYYMMDD_HHMMSS_query_опис.php</pre>
      <p>Такі міграції позначаються міткою «запит до БД». Усередині запишіть результат у файл репозиторію через <code>$repoDir</code>:</p>
      <pre style="background:#0f172a;color:#e2e8f0;padding:.6rem .8rem;border-radius:8px;font-size:.78rem;overflow:auto">&lt;?php
$row = $pdo->query("SELECT COUNT(*) AS cnt FROM " . jura_table('pages'))->fetch();
file_put_contents($repoDir . '/migrations/results/pages_count.md', "Сторінок: " . $row['cnt']);
echo "Готово\n";</pre>
      <p>Після виконання такої міграції перейдіть на <strong>Зміни у файлах</strong> вище і закомітьте файл(и) результату — лише після коміту AI зможе прочитати їх у репозиторії.</p>
    </div>
  </details>
</section>

<?php $curAuth = $settings['gitdeploy_auth_type'] ?? 'none'; ?>
<details class="jura-card" style="margin-bottom:1rem" <?= ($curAuth === 'none' || !empty($flash_ssh_key)) ? 'open' : '' ?>>
  <summary style="cursor:pointer;font-weight:700">🔑 Налаштування: підключення та автор комітів</summary>
  <div class="jura-alert" style="margin-top:1rem">
    Один спільний блок — раніше "Налаштування" (ім'я/email) і зміна автентифікації були двома окремими формами, і збереження одної не чіпало іншу, через що легко було "зберегти" тільки ім'я/email і не помітити, що спосіб автентифікації лишився старим. Тепер усе зберігається однією кнопкою. Змінювати тут адресу/автентифікацію безпечно — файли сайту не чіпаються. Якщо <strong>git pull</strong> падає з помилкою <code>could not read Username for 'https://github.com'</code> — це означає, що нижче обрано «Без автентифікації» для приватного репозиторію.
  </div>
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
    <div class="jura-grid jura-grid-2" style="gap:1rem;margin-top:1rem">
      <div>
        <label class="jura-label">Адреса репозиторію</label>
        <input class="jura-input" name="remote_url" value="<?= e($info['remote'] ?? '') ?>" required>
      </div>
      <div>
        <label class="jura-label">Гілка</label>
        <input class="jura-input" name="branch" value="<?= e($info['branch'] ?? 'main') ?>">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Автентифікація</label>
      <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.4rem">
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="none" <?= $curAuth === 'none' ? 'checked' : '' ?> onchange="gdToggleAuthReconnect(this)"> Без автентифікації (публічний репозиторій)</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="https_token" <?= $curAuth === 'https_token' ? 'checked' : '' ?> onchange="gdToggleAuthReconnect(this)"> HTTPS-токен</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="auth_type" value="ssh_key" <?= $curAuth === 'ssh_key' ? 'checked' : '' ?> onchange="gdToggleAuthReconnect(this)"> SSH-ключ</label>
      </div>
      <?php if ($curAuth === 'none'): ?>
      <p style="color:#9f1239;font-size:.82rem;margin:.5rem 0 0">Зараз обрано «Без автентифікації» — якщо репозиторій приватний, git pull/push будуть падати з <code>could not read Username</code>.</p>
      <?php endif; ?>
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
    <div style="margin-top:1rem">
      <label class="jura-label">Ліміт часу на одну міграцію, сек (якщо порожньо — значення сервера)</label>
      <input class="jura-input" type="number" min="0" step="1" name="migration_timeout" value="<?= e($settings['gitdeploy_migration_timeout'] ?? '') ?>" placeholder="Наприклад: 120" style="max-width:200px">
      <p style="color:#64748b;font-size:.8rem;margin:.35rem 0 0">Якщо міграція виконується довше цього часу, PHP примусово її зупинить — це буде видно у виводі як «перевищено ліміт часу» замість тихого зависання. Міграція лишається невиконаною, тож її можна безпечно запустити повторно (наприклад, розбивши на менші частини).</p>
    </div>
    <button class="jura-btn jura-btn-primary" type="submit" style="margin-top:1.2rem">Зберегти</button>
  </form>
  <script>
  function gdToggleAuthReconnect(radio) {
    document.getElementById('gd-auth-token-reconnect').style.display = radio.value === 'https_token' ? 'block' : 'none';
    document.getElementById('gd-auth-ssh-reconnect').style.display = radio.value === 'ssh_key' ? 'block' : 'none';
  }
  </script>
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
