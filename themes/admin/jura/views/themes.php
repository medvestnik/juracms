<?php $themes = $themes ?? []; $activeTheme = $active_theme ?? 'default'; ?>

<div style="margin-bottom:1.5rem">
  <p style="color:#64748b;font-size:.9rem;margin:0">
    Фронтенд-шаблони з директорії <code>themes/frontend/</code>.
    Активний шаблон застосовується до всіх сторінок сайту.
  </p>
</div>

<?php if (empty($themes)): ?>
<section class="jura-card">
  <p style="color:#888">Шаблонів не знайдено. Помістіть тему у директорію <code>themes/frontend/</code>.</p>
</section>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem">
  <?php foreach ($themes as $t): ?>
  <?php $isActive = $t['active'] ?? false; ?>
  <section class="jura-card" style="display:flex;flex-direction:column;gap:.75rem<?= $isActive ? ';border:2px solid var(--jura-accent,#6366f1)' : '' ?>">

    <div style="display:flex;align-items:flex-start;gap:.75rem">
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.25rem;flex-wrap:wrap">
          <strong style="font-size:1rem"><?= e($t['name'] ?? $t['slug']) ?></strong>
          <span style="font-size:.75rem;color:#94a3b8">v<?= e($t['version'] ?? '1.0.0') ?></span>
          <?php if ($isActive): ?>
          <span style="font-size:.72rem;background:#e0e7ff;color:#3730a3;border:1px solid #a5b4fc;border-radius:20px;padding:.1rem .5rem">✓ Активний</span>
          <?php else: ?>
          <span style="font-size:.72rem;background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;border-radius:20px;padding:.1rem .5rem">Не активний</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($t['description'])): ?>
        <p style="font-size:.85rem;color:#64748b;margin:0 0 .25rem"><?= e($t['description']) ?></p>
        <?php endif; ?>
        <?php if (!empty($t['author'])): ?>
        <p style="font-size:.75rem;color:#94a3b8;margin:0">Автор: <?= e($t['author']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:auto">
      <a href="/admin/themes/<?= e($t['slug']) ?>" class="jura-btn jura-btn-secondary" style="padding:.35rem .75rem;font-size:.83rem">
        Переглянути файли
      </a>
      <?php if (!$isActive): ?>
      <form method="post" style="margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="slug" value="<?= e($t['slug']) ?>">
        <input type="hidden" name="action" value="activate">
        <button type="submit" class="jura-btn jura-btn-primary" style="padding:.35rem .75rem;font-size:.83rem"
          onclick="return confirm('Активувати шаблон «<?= e($t['name'] ?? $t['slug']) ?>»?')">
          Активувати
        </button>
      </form>
      <?php else: ?>
      <a href="/" target="_blank" class="jura-btn jura-btn-secondary" style="padding:.35rem .75rem;font-size:.83rem">
        ↗ Переглянути сайт
      </a>
      <?php endif; ?>
    </div>
  </section>
  <?php endforeach; ?>
</div>
<?php endif; ?>
