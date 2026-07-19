<?php $theme = $theme ?? []; $files = $files ?? []; ?>

<div style="margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
  <a href="/admin/themes" class="jura-btn jura-btn-secondary" style="padding:.3rem .7rem">← Шаблони</a>
  <?php if ($theme['active'] ?? false): ?>
  <span style="font-size:.78rem;background:#e0e7ff;color:#3730a3;border:1px solid #a5b4fc;border-radius:20px;padding:.2rem .65rem">✓ Активний шаблон</span>
  <a href="/" target="_blank" class="jura-btn jura-btn-secondary" style="padding:.3rem .7rem;font-size:.83rem">↗ Переглянути сайт</a>
  <?php else: ?>
  <form method="post" action="/admin/themes" style="margin:0">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="slug" value="<?= e($theme['slug'] ?? '') ?>">
    <input type="hidden" name="action" value="activate">
    <button type="submit" class="jura-btn jura-btn-primary" style="padding:.3rem .75rem;font-size:.83rem"
      onclick="return confirm('Активувати шаблон «<?= e($theme['name'] ?? '') ?>»?')">
      Активувати
    </button>
  </form>
  <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:1rem;align-items:start">

  <!-- Info card -->
  <section class="jura-card" style="font-size:.88rem">
    <h2 style="margin-top:0;font-size:1rem"><?= e($theme['name'] ?? $theme['slug'] ?? '') ?></h2>
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td style="padding:.3rem 0;color:#94a3b8;white-space:nowrap;padding-right:.75rem">Slug</td>
        <td><code style="font-size:.82rem"><?= e($theme['slug'] ?? '') ?></code></td>
      </tr>
      <tr>
        <td style="padding:.3rem 0;color:#94a3b8;padding-right:.75rem">Версія</td>
        <td><?= e($theme['version'] ?? '—') ?></td>
      </tr>
      <tr>
        <td style="padding:.3rem 0;color:#94a3b8;padding-right:.75rem">Автор</td>
        <td><?= e($theme['author'] ?? '—') ?></td>
      </tr>
      <tr>
        <td style="padding:.3rem 0;color:#94a3b8;padding-right:.75rem">Тип</td>
        <td><?= e($theme['type'] ?? 'frontend') ?></td>
      </tr>
      <?php if (!empty($theme['description'])): ?>
      <tr>
        <td style="padding:.3rem 0;color:#94a3b8;vertical-align:top;padding-right:.75rem">Опис</td>
        <td style="color:#374151"><?= e($theme['description']) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </section>

  <!-- File tree -->
  <section class="jura-card">
    <h2 style="margin-top:0;font-size:1rem">Файли шаблону
      <span style="font-weight:400;font-size:.8rem;color:#94a3b8">(<?= count($files) ?>)</span>
    </h2>
    <?php if (empty($files)): ?>
    <p style="color:#888;font-size:.88rem">Файлів не знайдено.</p>
    <?php else: ?>
    <div style="font-family:monospace;font-size:.82rem;line-height:1.7">
    <?php
    $prevParts = [];
    foreach ($files as $file):
        $parts = explode('/', $file);
        $filename = array_pop($parts);
        $depth = count($parts);

        // Show directory headers when path changes
        foreach ($parts as $i => $dir):
            if (!isset($prevParts[$i]) || $prevParts[$i] !== $dir):
    ?>
    <div style="margin-top:<?= $i === 0 ? '.6rem' : '.1rem' ?>;padding-left:<?= $i * 1.25 ?>rem;color:#64748b;font-weight:600">
      📁 <?= e($dir) ?>/
    </div>
    <?php
            endif;
        endforeach;
        $prevParts = $parts;

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $icon = match($ext) {
            'php' => '🐘',
            'css' => '🎨',
            'js'  => '⚡',
            'json'=> '📋',
            'md'  => '📝',
            default => '📄',
        };
    ?>
    <div style="padding-left:<?= ($depth * 1.25) + .5 ?>rem;color:#1e293b">
      <?= $icon ?> <?= e($filename) ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

</div>
