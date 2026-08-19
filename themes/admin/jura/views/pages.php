<?php $edit = $edit ?? null; ?>
<?php if ($edit !== null): $p = $edit; ?>
<?php if (!empty($p['id'])):
  $translations = $translations ?? [];
  $allLocales = $all_locales ?? [];
?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Переклади</h2>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap">
    <?php foreach ($allLocales as $l): $code = $l['code']; $t = $translations[$code] ?? null; ?>
      <?php if ($t): ?>
      <a class="jura-btn <?= (int) $t['id'] === (int) $p['id'] ? 'jura-btn-primary' : 'jura-btn-secondary' ?>" style="font-size:.85rem"
        href="/admin/pages/<?= (int) $t['id'] ?>/edit"><?= e($l['native_name']) ?> (<?= e($code) ?>)<?= (int) $t['id'] === (int) $p['id'] ? ' — поточна' : '' ?></a>
      <?php else: ?>
      <form method="post" action="/admin/pages/<?= (int) $p['id'] ?>/translate" style="margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="locale" value="<?= e($code) ?>">
        <button class="jura-btn" type="submit" style="font-size:.85rem;background:#f8fafc;color:#475569;border:1px dashed #cbd5e1">+ <?= e($l['native_name']) ?> (<?= e($code) ?>)</button>
      </form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<section class="jura-card">
  <h2 style="margin-top:0"><?= $p ? 'Редагувати сторінку' : 'Нова сторінка' ?><?= !empty($p['locale']) ? ' <span style="font-size:.75rem;font-weight:400;color:#94a3b8">(' . e($p['locale']) . ')</span>' : '' ?></h2>
  <form method="post" action="<?= e($p ? ('/admin/pages/' . $p['id']) : '/admin/pages') ?>">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Заголовок</label>
        <input class="jura-input" name="title" value="<?= e($p['title'] ?? '') ?>" required>
      </div>
      <div>
        <label class="jura-label">Slug</label>
        <input class="jura-input" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="автоматично з заголовку">
      </div>
      <div>
        <label class="jura-label">URL сторінки</label>
        <input class="jura-input" name="route_path" value="<?= e($p['route_path'] ?? '') ?>" placeholder="/про-нас">
      </div>
      <div>
        <label class="jura-label">Сортування</label>
        <input class="jura-input" type="number" name="sort_order" value="<?= e((string) ($p['sort_order'] ?? 0)) ?>">
      </div>
      <div>
        <label class="jura-label">Статус</label>
        <select class="jura-input" name="status">
          <?php foreach (['draft' => 'Чернетка', 'published' => 'Опубліковано', 'hidden' => 'Прихована'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($p['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="jura-label">Шаблон</label>
        <select class="jura-input" name="template">
          <?php foreach (['page' => 'Сторінка', 'home' => 'Головна', 'blog' => 'Блог'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($p['template'] ?? 'page') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:1rem">
      <label class="jura-label">Короткий опис</label>
      <textarea class="jura-input" name="excerpt" rows="2"><?= e($p['excerpt'] ?? '') ?></textarea>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Вміст</label>
      <textarea class="jura-input" name="content" data-editor="simple-js-editor" rows="12"><?= e($p['content'] ?? '') ?></textarea>
    </div>

    <h3 style="margin:1.5rem 0 .5rem">SEO</h3>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Meta title</label>
        <input class="jura-input" name="meta_title" value="<?= e($p['meta_title'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Meta keywords</label>
        <input class="jura-input" name="meta_keywords" value="<?= e($p['meta_keywords'] ?? '') ?>">
      </div>
      <div style="grid-column:1 / -1">
        <label class="jura-label">Meta description</label>
        <input class="jura-input" name="meta_description" value="<?= e($p['meta_description'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Canonical URL</label>
        <input class="jura-input" name="canonical_path" value="<?= e($p['canonical_path'] ?? '') ?>" placeholder="/про-нас">
      </div>
      <div>
        <label class="jura-label">OG title</label>
        <input class="jura-input" name="og_title" value="<?= e($p['og_title'] ?? '') ?>">
      </div>
      <div style="grid-column:1 / -1">
        <label class="jura-label">OG description</label>
        <input class="jura-input" name="og_description" value="<?= e($p['og_description'] ?? '') ?>">
      </div>
    </div>

    <div style="margin-top:1.5rem">
      <button class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
      <button class="jura-btn jura-btn-secondary" type="submit" name="_close" value="1">Зберегти і вийти</button>
      <a class="jura-btn jura-btn-secondary" href="/admin/pages">Скасувати</a>
    </div>
  </form>
</section>

<?php else: ?>
<div style="margin-bottom:1rem">
  <a class="jura-btn jura-btn-primary" href="/admin/pages/create">+ Додати сторінку</a>
</div>
<section class="jura-card">
  <?php if (empty($pages)): ?>
  <p style="color:#888">Сторінок ще немає.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Заголовок</th><th>URL</th><th>Статус</th><th>Шаблон</th><th>Оновлено</th><th>Дії</th></tr></thead>
    <tbody>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td><?= e($p['title']) ?></td>
        <td><code><?= e($p['route_path'] ?? '') ?></code></td>
        <td>
          <?php $statusLabels = ['published' => ['Опубліковано', '#d1fae5', '#065f46', '#6ee7b7'], 'draft' => ['Чернетка', '#f3f4f6', '#6b7280', '#d1d5db'], 'hidden' => ['Прихована', '#fef3c7', '#92400e', '#fde68a']];
          $sl = $statusLabels[$p['status']] ?? $statusLabels['draft']; ?>
          <span style="font-size:.72rem;background:<?= $sl[1] ?>;color:<?= $sl[2] ?>;border:1px solid <?= $sl[3] ?>;border-radius:20px;padding:.1rem .5rem"><?= $sl[0] ?></span>
        </td>
        <td><?= e($p['template'] ?? 'page') ?></td>
        <td><?= e($p['updated_at'] ?? '') ?></td>
        <td style="white-space:nowrap;display:flex;gap:.4rem;align-items:center">
          <a class="jura-btn jura-btn-secondary" style="padding:.3rem .6rem;font-size:.8rem" href="/admin/pages/<?= (int) $p['id'] ?>/edit">Редагувати</a>
          <?php if (!empty($p['route_path'])): ?>
          <a class="jura-btn jura-btn-secondary" style="padding:.3rem .6rem;font-size:.8rem" href="<?= e($p['route_path']) ?>" target="_blank" rel="noopener">👁</a>
          <?php endif; ?>
          <form method="post" action="/admin/pages/<?= (int) $p['id'] ?>/toggle" style="margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .6rem;font-size:.8rem">
              <?= $p['status'] === 'published' ? 'Зняти з публікації' : 'Опублікувати' ?>
            </button>
          </form>
          <form method="post" action="/admin/pages/<?= (int) $p['id'] ?>/delete" style="margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button class="jura-btn" type="submit" style="padding:.3rem .6rem;font-size:.8rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3"
              onclick="return confirm('Видалити сторінку «<?= e($p['title']) ?>»?')">✕</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?php endif; ?>
