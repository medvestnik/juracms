<?php
$menus     = $menus ?? [];
$items     = $items ?? [];
$menuPages = $menu_pages ?? [];
$itemsByMenu = [];
foreach ($items as $item) {
    $itemsByMenu[(int) $item['menu_id']][] = $item;
}
?>
<div style="margin-bottom:1.25rem">
  <p style="color:#64748b;font-size:.9rem;margin:0">
    Меню хедера: <code><?= e($menu_header ?? 'main') ?></code> &middot;
    Меню футера: <code><?= e($menu_footer ?? 'main') ?></code>
    &mdash; коди можна змінити на сторінці <a href="/admin/settings">Налаштування</a>.
  </p>
</div>

<section class="jura-card" style="margin-bottom:1.25rem">
  <h2 style="margin-top:0">Створити меню</h2>
  <form method="post" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create_menu">
    <div>
      <label class="jura-label">Код</label>
      <input class="jura-input" name="code" placeholder="main" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Назва</label>
      <input class="jura-input" name="name" placeholder="Main menu" style="margin:0" required>
    </div>
    <button class="jura-btn jura-btn-primary" type="submit">Створити</button>
  </form>
</section>

<?php if (empty($menus)): ?>
<section class="jura-card"><p style="color:#888">Меню ще не створено.</p></section>
<?php endif; ?>

<?php foreach ($menus as $menu): $mid = (int) $menu['id']; ?>
<section class="jura-card" style="margin-bottom:1.25rem">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:.6rem">
      <h2 style="margin:0"><?= e($menu['name']) ?></h2>
      <span class="jura-badge"><?= e($menu['code']) ?></span>
    </div>
    <div style="display:flex;gap:.5rem">
      <form method="post" style="display:flex;gap:.4rem;margin:0" onsubmit="var n=prompt('Нова назва меню:', <?= json_encode($menu['name']) ?>); if(n===null||n==='') return false; this.querySelector('[name=name]').value=n;">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="rename_menu">
        <input type="hidden" name="id" value="<?= $mid ?>">
        <input type="hidden" name="name" value="">
        <button class="jura-btn jura-btn-secondary" type="submit">Перейменувати</button>
      </form>
      <form method="post" style="margin:0" onsubmit="return confirm('Видалити меню «<?= e($menu['name']) ?>» разом з усіма пунктами?')">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_menu">
        <input type="hidden" name="id" value="<?= $mid ?>">
        <button class="jura-btn" type="submit" style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3">Видалити меню</button>
      </form>
    </div>
  </div>

  <?php $menuItems = $itemsByMenu[$mid] ?? []; ?>
  <?php if (empty($menuItems)): ?>
  <p style="color:#888;font-size:.9rem">Пунктів ще немає.</p>
  <?php else: ?>
  <table class="jura-table" style="margin-bottom:1rem">
    <thead><tr><th style="width:44px">#</th><th>Назва</th><th>URL</th><th style="width:90px">Порядок</th><th style="width:110px">Дії</th></tr></thead>
    <tbody>
    <?php foreach ($menuItems as $item): ?>
      <tr>
        <td><?= (int) $item['id'] ?></td>
        <td><?= e($item['title']) ?></td>
        <td><code><?= e($item['url']) ?></code></td>
        <td><?= (int) $item['sort_order'] ?></td>
        <td>
          <form method="post" style="display:inline;margin:0" onsubmit="return confirm('Видалити пункт «<?= e($item['title']) ?>»?')">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <button class="jura-btn" type="submit" style="padding:.25rem .55rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3">✕</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <form method="post" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_item">
    <input type="hidden" name="menu_id" value="<?= $mid ?>">
    <div>
      <label class="jura-label">Назва</label>
      <input class="jura-input" name="title" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">URL</label>
      <input class="jura-input" name="url" placeholder="/about" list="menu-pages-<?= $mid ?>" style="margin:0" required>
      <datalist id="menu-pages-<?= $mid ?>">
        <?php foreach ($menuPages as $mp): ?>
        <option value="/<?= e(ltrim((string) ($mp['slug'] ?? ''), '/')) ?>"><?= e($mp['title']) ?></option>
        <?php endforeach; ?>
      </datalist>
    </div>
    <div>
      <label class="jura-label">Порядок</label>
      <input class="jura-input" type="number" name="sort_order" value="0" style="margin:0;max-width:90px">
    </div>
    <button class="jura-btn jura-btn-secondary" type="submit">+ Додати пункт</button>
  </form>
</section>
<?php endforeach; ?>
