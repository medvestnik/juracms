<?php $media = $media ?? []; ?>
<?php if (!empty($success)): ?>
<div class="jura-alert" style="margin-bottom:1rem"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239"><?= e($error) ?></div>
<?php endif; ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Завантажити файл</h2>
  <form method="post" action="/admin/media/upload" enctype="multipart/form-data" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="file" name="file" class="jura-input" style="flex:1;min-width:200px;margin:0" required>
    <input type="text" name="alt" class="jura-input" placeholder="Alt текст" style="max-width:220px;margin:0">
    <button class="jura-btn jura-btn-primary" type="submit">Завантажити</button>
  </form>
</section>

<section class="jura-card">
  <?php if (empty($media)): ?>
  <p style="color:#888">Файлів ще немає.</p>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem">
    <?php foreach ($media as $m): ?>
    <div style="border:1px solid var(--jura-border,#e2e8f0);border-radius:10px;overflow:hidden">
      <?php if (str_starts_with((string) ($m['mime_type'] ?? ''), 'image/')): ?>
      <img src="/<?= e(ltrim((string) $m['path'], '/')) ?>" alt="<?= e($m['alt'] ?? '') ?>" style="width:100%;height:120px;object-fit:cover;display:block">
      <?php else: ?>
      <div style="width:100%;height:120px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;font-size:2rem">📄</div>
      <?php endif; ?>
      <div style="padding:.5rem .6rem;font-size:.78rem;color:#64748b;word-break:break-all"><?= e($m['original_name'] ?? $m['filename'] ?? '') ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
