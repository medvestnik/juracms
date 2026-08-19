<?php
$p  = $promotion ?? [];
$id = (int) ($p['id'] ?? 0);
$isCreate = $id === 0;
$formAction = $isCreate ? '/admin/hotel/promotions/create' : '/admin/hotel/promotions/' . $id . '/edit';
?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
  <a href="/admin/hotel/promotions" class="jura-btn jura-btn-secondary">&larr; Назад</a>
  <h1 style="margin:0"><?= $isCreate ? 'Нова акція' : 'Редагувати акцію' ?></h1>
</div>
<form method="post" action="<?= e($formAction) ?>">
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
    <div style="margin-top:1rem">
      <label class="jura-label">Короткий опис</label>
      <textarea class="jura-input" name="excerpt" rows="3"><?= e($p['excerpt'] ?? '') ?></textarea>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Зміст</h2>
    <label class="jura-label">Текст акції</label>
    <textarea class="jura-input" name="content" data-editor="simple-js-editor" rows="12"><?= e($p['content'] ?? '') ?></textarea>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">SEO та публікація</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Meta title</label>
        <input class="jura-input" name="meta_title" value="<?= e($p['meta_title'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Порядок сортування</label>
        <input class="jura-input" name="sort_order" type="number" value="<?= e($p['sort_order'] ?? '0') ?>">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Meta description</label>
      <textarea class="jura-input" name="meta_description" rows="2"><?= e($p['meta_description'] ?? '') ?></textarea>
    </div>
    <div class="jura-grid jura-grid-2" style="gap:1rem;margin-top:1rem">
      <div>
        <label class="jura-label">Статус</label>
        <select class="jura-input" name="status" style="max-width:200px">
          <?php foreach (['draft' => 'Чернетка', 'published' => 'Опублікований'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($p['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="jura-label">Мова</label>
        <select class="jura-input" name="locale" style="max-width:200px">
          <option value="" <?= empty($p['locale']) ? 'selected' : '' ?>>Всі мови</option>
          <?php foreach (($all_locales ?? []) as $l): ?>
            <option value="<?= e($l['code']) ?>" <?= ($p['locale'] ?? '') === $l['code'] ? 'selected' : '' ?>><?= e($l['native_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </section>

  <div style="display:flex;gap:1rem">
    <button class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
    <a class="jura-btn jura-btn-secondary" href="/admin/hotel/promotions">Скасувати</a>
  </div>
</form>
