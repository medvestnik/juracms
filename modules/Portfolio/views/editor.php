<?php
$isProject = $kind === 'project';
$id = (int) ($item['id'] ?? 0);
$featuredImg = !empty($item['featured_image']) ? '/public/userfiles/portfolio/' . $item['featured_image'] : null;
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap">
  <a href="<?= e($base) ?>" class="jura-btn jura-btn-secondary">&larr; Назад</a>
  <div style="display:flex;gap:.6rem">
    <a class="jura-btn jura-btn-secondary" href="<?= e($base) ?>">Скасувати</a>
    <button form="portfolio-item-form" class="jura-btn jura-btn-secondary" type="submit" name="_close" value="1">Зберегти і закрити</button>
    <button form="portfolio-item-form" class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
  </div>
</div>

<form id="portfolio-item-form" method="post">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= $id ?>">

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Основне</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Назва</label>
        <input class="jura-input" name="title" required value="<?= e($item['title'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Категорія / тип</label>
        <input class="jura-input" name="category" required value="<?= e($item['category'] ?? '') ?>">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Опис</label>
      <textarea class="jura-input" name="description" rows="6" required><?= e($item['description'] ?? '') ?></textarea>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Посилання</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Посилання (URL)</label>
        <input class="jura-input" type="url" name="url" required value="<?= e($item['url'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Текст посилання</label>
        <input class="jura-input" name="link_label" required value="<?= e($item['link_label'] ?? '') ?>">
      </div>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Вигляд картки та публікація</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <?php if ($isProject): ?>
      <div>
        <label class="jura-label">Статус на картці</label>
        <input class="jura-input" name="status_label" value="<?= e($item['status_label'] ?? '') ?>">
      </div>
      <?php else: ?>
      <input type="hidden" name="status_label" value="">
      <?php endif; ?>
      <div>
        <label class="jura-label">Колір</label>
        <select class="jura-input" name="color">
          <?php foreach (($isProject ? ['default', 'feature', 'accent'] : ['blue', 'orange', 'ink', 'acid']) as $color): ?>
          <option <?= ($item['color'] ?? '') === $color ? 'selected' : '' ?>><?= e($color) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="jura-label">Мова</label>
        <select class="jura-input" name="locale">
          <option value="" <?= empty($item['locale']) ? 'selected' : '' ?>>Всі мови</option>
          <?php foreach (($all_locales ?? []) as $l): ?>
          <option value="<?= e($l['code']) ?>" <?= ($item['locale'] ?? '') === $l['code'] ? 'selected' : '' ?>><?= e($l['native_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="jura-label">Порядок</label>
        <input class="jura-input" type="number" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
      </div>
    </div>
    <div style="display:flex;gap:1.5rem;margin-top:1rem">
      <label style="display:flex;gap:.5rem;align-items:center">
        <input type="checkbox" name="published" value="1" <?= ($item['status'] ?? 'published') === 'published' ? 'checked' : '' ?>> Опубліковано
      </label>
      <label style="display:flex;gap:.5rem;align-items:center">
        <input type="checkbox" name="featured_home" value="1" <?= !empty($item['featured_home']) ? 'checked' : '' ?>> На головній
      </label>
    </div>
  </section>
</form>

<?php if ($id): ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Зображення картки</h2>
  <?php if ($featuredImg): ?>
  <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap">
    <img src="<?= e($featuredImg) ?>" alt="" style="width:240px;height:160px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
    <form method="post" action="<?= e($base) ?>/<?= $id ?>/upload-image" style="margin:0">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="remove_image" value="1">
      <button class="jura-btn" type="submit" onclick="return confirm('Видалити зображення?')"
        style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:.3rem .7rem">&#10005; Видалити</button>
    </form>
  </div>
  <?php else: ?>
  <p style="color:#94a3b8;font-size:.875rem;margin-bottom:.75rem">Зображення не завантажено — картка показуватиме лише колір.</p>
  <?php endif; ?>
  <form method="post" action="<?= e($base) ?>/<?= $id ?>/upload-image" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
      <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" class="jura-input" style="flex:1;min-width:200px">
      <button class="jura-btn jura-btn-primary" type="submit">Завантажити</button>
    </div>
    <small style="color:#94a3b8">JPG, PNG або WebP. Замінить поточне зображення.</small>
  </form>
</section>
<?php else: ?>
<div class="jura-card" style="color:#94a3b8;font-size:.875rem">
  &#9757; Спочатку збережіть запис — після цього стане доступне завантаження зображення.
</div>
<?php endif; ?>
