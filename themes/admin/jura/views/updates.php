<?php
$pending = $pending_migrations ?? [];
$applied = $applied_migrations ?? [];
?>
<?php if (!empty($flash_success)): ?>
<div class="jura-alert" style="margin-bottom:1rem;white-space:pre-line"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239;white-space:pre-line"><?= e($flash_error) ?></div>
<?php endif; ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Стан системи</h2>
  <table style="width:100%;border-collapse:collapse;font-size:.9rem">
    <tr><td style="padding:.3rem 0;color:#94a3b8;width:180px">Версія</td><td><?= e($current_version ?? 'n/a') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Встановлено</td><td><?= e($installed_at ?? '—') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Git remote</td><td><code><?= e($git_remote ?? '—') ?></code></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Git гілка</td><td><?= e($git_branch ?? '—') ?></td></tr>
    <tr><td style="padding:.3rem 0;color:#94a3b8">Останній коміт</td><td><?= e($git_last_commit ?? '—') ?></td></tr>
  </table>
  <form method="post" style="margin-top:1rem">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="git_pull">
    <button class="jura-btn jura-btn-secondary" type="submit" onclick="return confirm('Виконати git pull?')">↻ git pull</button>
  </form>
</section>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Міграції, що очікують (<?= count($pending) ?>)</h2>
  <?php if (empty($pending)): ?>
  <p style="color:#888">Немає нових міграцій.</p>
  <?php else: ?>
  <p style="color:#64748b;font-size:.88rem">Це разові доповнення до вмісту (сторінка «Дякуємо», поле «Телефон 2», демо-публікації тощо) — їх безпечно виконати, при повторному запуску вже застосовані міграції пропускаються.</p>
  <ul style="margin:0;padding-left:1.2rem">
    <?php foreach ($pending as $m): ?>
    <li><code><?= e($m['file']) ?></code> — <?= e($m['status']) ?></li>
    <?php endforeach; ?>
  </ul>
  <form method="post" style="margin-top:1rem">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="run_migrations">
    <button class="jura-btn jura-btn-primary" type="submit">Виконати міграції</button>
  </form>
  <?php endif; ?>
</section>

<section class="jura-card" style="margin-top:1rem">
  <h2 style="margin-top:0">Останні помилки (logs/php-error.log)</h2>
  <?php if (empty($error_log_tail)): ?>
  <p style="color:#888">Файл лог помилок порожній або ще не створений.</p>
  <?php else: ?>
  <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;max-height:420px;font-size:.78rem;line-height:1.5;white-space:pre-wrap"><?= e($error_log_tail) ?></pre>
  <p style="color:#94a3b8;font-size:.8rem;margin-top:.5rem">Показано останні 100 рядків.</p>
  <?php endif; ?>
</section>

<section class="jura-card">
  <h2 style="margin-top:0">Виконані міграції</h2>
  <?php if (empty($applied)): ?>
  <p style="color:#888">Ще не виконано жодної міграції.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Файл</th><th>Виконано</th></tr></thead>
    <tbody>
    <?php foreach ($applied as $a): ?>
      <tr><td><code><?= e($a['migration']) ?></code></td><td><?= e($a['executed_at'] ?? '') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
