<?php $categories = $categories ?? []; ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Додати категорію</h2>
  <form method="post" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="jura-label">Назва</label>
      <input class="jura-input" name="title" placeholder="Новини" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Slug (URL)</label>
      <input class="jura-input" name="slug" placeholder="auto-generated" style="margin:0">
    </div>
    <div>
      <label class="jura-label">Опис</label>
      <input class="jura-input" name="description" style="margin:0">
    </div>
    <div>
      <label class="jura-label">Сортування</label>
      <input class="jura-input" type="number" name="sort_order" value="<?= count($categories) ?>" style="max-width:80px;margin:0">
    </div>
    <button class="jura-btn jura-btn-primary" type="submit">Додати</button>
  </form>
</section>

<section class="jura-card">
  <h2 style="margin-top:0">Категорії публікацій</h2>
  <?php if (empty($categories)): ?>
  <p style="color:#64748b">Категорій ще немає.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Назва</th><th>Slug</th><th>Опис</th><th>Сортування</th><th>Публікацій</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
    <tr>
      <td>
        <form method="post" id="cat-<?= (int) $c['id'] ?>" style="display:none">
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        </form>
        <input form="cat-<?= (int) $c['id'] ?>" class="jura-input" name="title" value="<?= e($c['title']) ?>" style="width:160px;margin:0" required>
      </td>
      <td><input form="cat-<?= (int) $c['id'] ?>" class="jura-input" name="slug" value="<?= e($c['slug']) ?>" style="width:140px;margin:0"></td>
      <td><input form="cat-<?= (int) $c['id'] ?>" class="jura-input" name="description" value="<?= e($c['description'] ?? '') ?>" style="width:200px;margin:0"></td>
      <td><input form="cat-<?= (int) $c['id'] ?>" class="jura-input" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" style="width:70px;margin:0"></td>
      <td><?= (int) $c['post_count'] ?></td>
      <td style="white-space:nowrap;display:flex;gap:.4rem">
        <button form="cat-<?= (int) $c['id'] ?>" class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .6rem;font-size:.8rem">✓</button>
        <form method="post" style="margin:0">
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <button class="jura-btn" type="submit" style="padding:.3rem .6rem;font-size:.8rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3"
            onclick="return confirm('Видалити категорію «<?= e($c['title']) ?>»?')">✕</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
