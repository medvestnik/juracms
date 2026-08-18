<?php $modules = $modules ?? []; ?>
<div style="margin-bottom:1.5rem">
  <p style="color:#64748b;font-size:.9rem;margin:0">Тут можна встановлювати та видаляти модулі розширення JuraCMS. Доступні модулі визначаються наявністю директорій у папці <code>modules/</code>.</p>
</div>

<?php if (empty($modules)): ?>
<section class="jura-card">
  <p style="color:#888">Доступних модулів не знайдено. Помістіть модуль у директорію <code>modules/</code> проекту.</p>
</section>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem">
  <?php foreach ($modules as $mod): ?>
  <section class="jura-card" style="display:flex;flex-direction:column;gap:.75rem">
    <div style="display:flex;align-items:flex-start;gap:.75rem">
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.2rem">
          <strong style="font-size:1rem"><?= e($mod['name']) ?></strong>
          <span style="font-size:.75rem;color:#94a3b8">v<?= e($mod['version'] ?? '1.0.0') ?></span>
          <?php if ($mod['installed'] && $mod['enabled']): ?>
          <span style="font-size:.72rem;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:20px;padding:.1rem .5rem">✓ Увімкнено</span>
          <?php elseif ($mod['installed']): ?>
          <span style="font-size:.72rem;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:.1rem .5rem">⏸ Вимкнено</span>
          <?php else: ?>
          <span style="font-size:.72rem;background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;border-radius:20px;padding:.1rem .5rem">Не встановлено</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($mod['description'])): ?>
        <p style="font-size:.85rem;color:#64748b;margin:0"><?= e($mod['description']) ?></p>
        <?php endif; ?>
        <?php if (!empty($mod['author'])): ?>
        <p style="font-size:.75rem;color:#94a3b8;margin:.25rem 0 0">Автор: <?= e($mod['author']) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <div style="display:flex;gap:.5rem;margin-top:auto">
      <?php if ($mod['installed']): ?>
      <form method="post" style="margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="slug" value="<?= e($mod['slug']) ?>">
        <?php if ($mod['enabled']): ?>
        <input type="hidden" name="action" value="disable">
        <button type="submit" class="jura-btn jura-btn-secondary" style="padding:.35rem .8rem;font-size:.83rem">
          Вимкнути
        </button>
        <?php else: ?>
        <input type="hidden" name="action" value="enable">
        <button type="submit" class="jura-btn jura-btn-primary" style="padding:.35rem .8rem;font-size:.83rem">
          Увімкнути
        </button>
        <?php endif; ?>
      </form>
      <form method="post" style="margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="slug" value="<?= e($mod['slug']) ?>">
        <input type="hidden" name="action" value="uninstall">
        <button type="submit" class="jura-btn" style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:.35rem .8rem;font-size:.83rem"
          onclick="return confirm('Видалити модуль «<?= e($mod['name']) ?>»? Дані не будуть видалені.')">
          Видалити модуль
        </button>
      </form>
      <?php else: ?>
      <form method="post" style="margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="slug" value="<?= e($mod['slug']) ?>">
        <input type="hidden" name="action" value="install">
        <button type="submit" class="jura-btn jura-btn-primary" style="padding:.35rem .8rem;font-size:.83rem">
          Встановити
        </button>
      </form>
      <?php endif; ?>
    </div>
  </section>
  <?php endforeach; ?>
</div>
<?php endif; ?>
