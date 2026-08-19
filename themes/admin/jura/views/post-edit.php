<?php
$p  = $post ?? [];
$id = (int) ($p['id'] ?? 0);
$isCreate = $id === 0;
$formAction = $isCreate ? '/admin/posts/create' : '/admin/posts/' . $id . '/edit';
$categories = $categories ?? [];
$featuredImg = !empty($p['featured_image']) ? '/public/userfiles/posts/' . $p['featured_image'] : null;
?>
<?php if (!$isCreate): $translations = $translations ?? []; $allLocales = $all_locales ?? []; ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Переклади</h2>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap">
    <?php foreach ($allLocales as $l): $code = $l['code']; $t = $translations[$code] ?? null; ?>
      <?php if ($t): ?>
      <a class="jura-btn <?= (int) $t['id'] === $id ? 'jura-btn-primary' : 'jura-btn-secondary' ?>" style="font-size:.85rem"
        href="/admin/posts/<?= (int) $t['id'] ?>/edit"><?= e($l['native_name']) ?> (<?= e($code) ?>)<?= (int) $t['id'] === $id ? ' — поточна' : '' ?></a>
      <?php else: ?>
      <form method="post" action="/admin/posts/<?= $id ?>/translate" style="margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="locale" value="<?= e($code) ?>">
        <button class="jura-btn" type="submit" style="font-size:.85rem;background:#f8fafc;color:#475569;border:1px dashed #cbd5e1">+ <?= e($l['native_name']) ?> (<?= e($code) ?>)</button>
      </form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap">
  <a href="/admin/posts" class="jura-btn jura-btn-secondary">&larr; Назад</a>
  <div style="display:flex;gap:.6rem">
    <a class="jura-btn jura-btn-secondary" href="/admin/posts">Скасувати</a>
    <button form="post-main-form" class="jura-btn jura-btn-secondary" type="submit" name="_close" value="1">Зберегти і закрити</button>
    <button form="post-main-form" class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
  </div>
</div>

<form id="post-main-form" method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Основне</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Назва <span style="color:red">*</span></label>
        <input class="jura-input" name="title" value="<?= e($p['title'] ?? '') ?>" required>
      </div>
      <div>
        <label class="jura-label">Slug (URL)</label>
        <input class="jura-input" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>
    </div>
    <?php if (!empty($categories)): ?>
    <div style="margin-top:1rem">
      <label class="jura-label">Категорія</label>
      <select class="jura-input" name="category_id" style="max-width:300px">
        <option value="">— без категорії —</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
      <input type="hidden" name="category_id" value="">
    <?php endif; ?>
    <div style="margin-top:1rem">
      <label class="jura-label">Короткий опис</label>
      <textarea class="jura-input" name="excerpt" rows="3"><?= e($p['excerpt'] ?? '') ?></textarea>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Зміст</h2>
    <label class="jura-label">Текст публікації</label>
    <textarea class="jura-input" name="content" data-editor="simple-js-editor" rows="14"><?= e($p['content'] ?? '') ?></textarea>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">SEO та публікація</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Meta title</label>
        <input class="jura-input" name="meta_title" value="<?= e($p['meta_title'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Дата публікації</label>
        <input class="jura-input" name="published_at" type="datetime-local"
          value="<?= e(!empty($p['published_at']) ? date('Y-m-d\TH:i', strtotime($p['published_at'])) : '') ?>">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Meta description</label>
      <textarea class="jura-input" name="meta_description" rows="2"><?= e($p['meta_description'] ?? '') ?></textarea>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Статус</label>
      <select class="jura-input" name="status" style="max-width:200px">
        <?php foreach (['draft' => 'Чернетка', 'published' => 'Опублікований'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($p['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </section>
</form>

<?php if (!$isCreate): ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Головне зображення</h2>
  <?php if ($featuredImg): ?>
  <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap">
    <img src="<?= e($featuredImg) ?>" alt="" style="width:240px;height:160px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
    <form method="post" action="/admin/posts/<?= $id ?>/upload-image" style="margin:0">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="remove_image" value="1">
      <button class="jura-btn" type="submit" onclick="return confirm('Видалити зображення?')"
        style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:.3rem .7rem">&#10005; Видалити</button>
    </form>
  </div>
  <?php else: ?>
  <p style="color:#94a3b8;font-size:.875rem;margin-bottom:.75rem">Головне зображення не завантажено.</p>
  <?php endif; ?>
  <form method="post" action="/admin/posts/<?= $id ?>/upload-image" enctype="multipart/form-data">
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
  &#9757; Спочатку збережіть публікацію &#8212; після цього стане доступне завантаження зображення.
</div>
<?php endif; ?>
