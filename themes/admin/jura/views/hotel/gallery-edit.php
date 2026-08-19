<?php
$g        = $gallery ?? [];
$id       = (int) ($g['id'] ?? 0);
$isCreate = $id === 0;
$formAction = $isCreate ? '/admin/hotel/galleries/create' : '/admin/hotel/galleries/' . $id . '/edit';
$images   = $gallery_images ?? [];
$uploadDir = BASE_PATH . '/public/userfiles/gallery/';
?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
  <a href="/admin/hotel/galleries" class="jura-btn jura-btn-secondary">&larr; Назад</a>
  <h1 style="margin:0"><?= $isCreate ? 'Нова галерея' : 'Редагувати галерею' ?></h1>
</div>

<form method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Основне</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Назва <span style="color:red">*</span></label>
        <input class="jura-input" name="title" value="<?= e($g['title'] ?? '') ?>" required>
      </div>
      <div>
        <label class="jura-label">Slug (URL)</label>
        <input class="jura-input" name="slug" value="<?= e($g['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Опис</label>
      <textarea class="jura-input" name="description" rows="3"><?= e($g['description'] ?? '') ?></textarea>
    </div>
    <div class="jura-grid jura-grid-2" style="gap:1rem;margin-top:1rem">
      <div>
        <label class="jura-label">Meta title</label>
        <input class="jura-input" name="meta_title" value="<?= e($g['meta_title'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Сортування</label>
        <input class="jura-input" name="sort_order" type="number" value="<?= e($g['sort_order'] ?? '0') ?>" style="max-width:80px">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Meta description</label>
      <textarea class="jura-input" name="meta_description" rows="2"><?= e($g['meta_description'] ?? '') ?></textarea>
    </div>
    <div class="jura-grid jura-grid-2" style="gap:1rem;margin-top:1rem">
      <div>
        <label class="jura-label">Статус</label>
        <select class="jura-input" name="status" style="max-width:200px">
          <?php foreach (['active' => 'Активна', 'hidden' => 'Прихована'] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($g['status'] ?? 'active') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="jura-label">Мова</label>
        <select class="jura-input" name="locale" style="max-width:200px">
          <option value="" <?= empty($g['locale']) ? 'selected' : '' ?>>Всі мови</option>
          <?php foreach (($all_locales ?? []) as $l): ?>
            <option value="<?= e($l['code']) ?>" <?= ($g['locale'] ?? '') === $l['code'] ? 'selected' : '' ?>><?= e($l['native_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </section>
  <div style="display:flex;gap:1rem;margin-bottom:1.5rem">
    <button class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
    <a class="jura-btn jura-btn-secondary" href="/admin/hotel/galleries">Скасувати</a>
  </div>
</form>

<?php if (!$isCreate): ?>
<!-- Upload images -->
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Завантажити фото</h2>
  <form method="post" action="/admin/hotel/galleries/<?= $id ?>/upload" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
      <div>
        <label class="jura-label">Виберіть фото (можна кілька)</label>
        <input class="jura-input" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
      </div>
      <button class="jura-btn jura-btn-primary" type="submit">Завантажити</button>
    </div>
  </form>
</section>

<!-- Current images -->
<section class="jura-card">
  <h2 style="margin-top:0">Фото в галереї (<?= count($images) ?>)</h2>
  <?php if (empty($images)): ?>
    <p style="color:#888">Фото ще немає.</p>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem">
    <?php foreach ($images as $img): ?>
    <div style="position:relative;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">
      <img src="/public/userfiles/gallery/<?= rawurlencode($img['title'] ?? $img['filename'] ?? '') ?>"
           alt="<?= e($img['alt'] ?? '') ?>"
           style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block">
      <div style="padding:8px;background:#fff">
        <div style="font-size:11px;color:#94a3b8;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e(pathinfo($img['title'] ?? '', PATHINFO_FILENAME)) ?></div>
        <input style="width:100%;font-size:12px;border:1px solid #cbd5e1;border-radius:4px;padding:2px 6px"
               placeholder="Alt текст" value="<?= e($img['alt'] ?? '') ?>"
               onblur="updateAlt(<?= $img['id'] ?>, this.value)">
      </div>
      <form method="post" action="/admin/hotel/galleries/<?= $id ?>/image/<?= $img['id'] ?>/delete" style="position:absolute;top:6px;right:6px">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <button type="submit" onclick="return confirm('Видалити?')"
          style="background:rgba(0,0,0,.55);border:none;border-radius:50%;width:26px;height:26px;cursor:pointer;color:#fff;font-size:14px;line-height:1">&times;</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<script>
function updateAlt(id, val) {
  fetch('/admin/hotel/galleries/image/' + id + '/alt', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'alt=' + encodeURIComponent(val)
  });
}
</script>
<?php endif; ?>
