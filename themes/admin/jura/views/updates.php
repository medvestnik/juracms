<?php
$check = $update_check ?? null;
?>
<?php if (!empty($flash_success)): ?>
<div class="jura-alert" style="margin-bottom:1rem;white-space:pre-line"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239;white-space:pre-line"><?= e($flash_error) ?></div>
<?php endif; ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Оновлення Jura CMS</h2>
  <p style="color:#64748b;font-size:.9rem">Поточна версія: <strong><?= e($current_version ?? 'n/a') ?></strong></p>

  <?php if ($check === null): ?>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="check_updates">
      <button class="jura-btn jura-btn-secondary" type="submit">Перевірити оновлення</button>
    </form>
  <?php elseif (!empty($check['error'])): ?>
    <div class="jura-alert" style="background:#fff1f2;border-color:#fecdd3;color:#9f1239;margin-bottom:1rem"><?= e($check['error']) ?></div>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="check_updates">
      <button class="jura-btn jura-btn-secondary" type="submit">Спробувати ще раз</button>
    </form>
  <?php elseif (empty($check['has_update'])): ?>
    <div class="jura-alert" style="background:#d1fae5;border-color:#6ee7b7;color:#065f46;margin-bottom:1rem">✓ У вас остання версія (v<?= e($check['latest_version'] ?? $current_version) ?>).</div>
    <form method="post">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="check_updates">
      <button class="jura-btn jura-btn-secondary" type="submit">Перевірити ще раз</button>
    </form>
  <?php else: ?>
    <div class="jura-alert jura-alert-info" style="margin-bottom:1rem">Доступне оновлення: <strong>v<?= e($check['latest_version']) ?></strong> (у вас v<?= e($current_version) ?>)</div>
    <?php if (!empty($check['release_notes'])): ?>
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1rem;max-height:260px;overflow:auto;font-size:.85rem;white-space:pre-wrap"><?= e($check['release_notes']) ?></div>
    <?php endif; ?>
    <p style="color:#64748b;font-size:.85rem;margin:0 0 .75rem"><code>config/</code> ніколи не чіпається. З <code>modules/</code> і <code>themes/</code> оновлюються лише спільні/невстановлені частини (адмінка, стандартна фронтенд-тема, ще не встановлені модулі) — уже наявний кастомний модуль чи фронтенд-тема цього сайту залишаються як є.</p>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
      <form method="post" id="update-now-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update_now">
        <button class="jura-btn jura-btn-primary" type="submit" id="update-now-btn" onclick="return confirm('Оновити Jura CMS до v<?= e($check['latest_version']) ?>? Перед оновленням буде створено резервну копію файлів у storage/backups/. config/ і кастомізовані modules/themes не будуть змінені.')">⬇ Оновити зараз</button>
      </form>
      <?php if (!empty($check['release_url'])): ?>
      <a class="jura-btn jura-btn-secondary" href="<?= e($check['release_url']) ?>" target="_blank" rel="noopener">Переглянути реліз на GitHub</a>
      <?php endif; ?>
    </div>
    <script>
    (function(){
      var form = document.getElementById('update-now-form');
      if (!form) return;
      form.addEventListener('submit', function(){
        var btn = document.getElementById('update-now-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Оновлення… не закривайте сторінку'; }
      });
    })();
    </script>
  <?php endif; ?>
</section>

<details class="jura-card" style="margin-bottom:1rem">
  <summary style="cursor:pointer;font-weight:700">Розширено: git</summary>
  <p style="color:#64748b;font-size:.85rem;margin:.75rem 0">
    Це працює лише якщо сайт задеплоєно з git-репозиторію і на хостингу дозволені shell-команди — підійде для власного форку/CI. Для звичайного сайту (завантажено як архів) використовуйте кнопку «Оновити зараз» вище.
  </p>
  <table style="width:100%;border-collapse:collapse;font-size:.9rem">
    <tr><td style="padding:.3rem 0;color:#94a3b8;width:180px">Встановлено</td><td><?= e($installed_at ?? '—') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Git remote</td><td><code><?= e($git_remote ?? '—') ?></code></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Git гілка</td><td><?= e($git_branch ?? '—') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Останній коміт</td><td><?= e($git_last_commit ?? '—') ?></td></tr>
  </table>
  <form method="post" style="margin-top:1rem">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="git_pull">
    <button class="jura-btn jura-btn-secondary" type="submit" onclick="return confirm('Виконати git pull?')">↻ git pull</button>
  </form>
</details>

<section class="jura-card" style="margin-top:1rem">
  <h2 style="margin-top:0">Останні помилки (logs/php-error.log)</h2>
  <?php if (empty($error_log_tail)): ?>
  <p style="color:#888">Файл лог помилок порожній або ще не створений.</p>
  <?php else: ?>
  <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;max-height:420px;font-size:.78rem;line-height:1.5;white-space:pre-wrap"><?= e($error_log_tail) ?></pre>
  <p style="color:#94a3b8;font-size:.8rem;margin-top:.5rem">Показано останні 100 рядків.</p>
  <?php endif; ?>
</section>
