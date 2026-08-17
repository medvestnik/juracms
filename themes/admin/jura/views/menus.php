<?php
$menus     = $menus ?? [];
$items     = $items ?? [];
$menuPages = $menu_pages ?? [];
$menuHeader = $menu_header ?? 'main';
$menuFooter = $menu_footer ?? 'main';

// Quick-pick list for the "add item" form: every published page.
$quickLinks = [];
foreach ($menuPages as $pg) {
    $url = ($pg['template'] ?? '') === 'home' ? '/' : '/' . ltrim((string) ($pg['slug'] ?? ''), '/');
    $quickLinks[$url] = $pg['title'];
}
?>

<?php if (!empty($menus)): ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Наявні меню</h2>
  <table class="jura-table">
    <thead><tr><th>Назва</th><th>Код</th><th>Використовується</th><th>Перейменувати</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($menus as $m): ?>
      <?php
        $usages = [];
        if ($m['code'] === $menuHeader) $usages[] = '🔝 Хедер';
        if ($m['code'] === $menuFooter) $usages[] = '⬇️ Футер';
      ?>
      <tr>
        <td><strong><?= e($m['name']) ?></strong></td>
        <td><code><?= e($m['code']) ?></code></td>
        <td><?= $usages ? implode(', ', $usages) : '<span style="color:#aaa">—</span>' ?></td>
        <td>
          <form method="post" style="display:flex;gap:.5rem;margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="rename_menu">
            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
            <input class="jura-input" name="name" value="<?= e($m['name']) ?>" style="max-width:180px;margin:0">
            <button class="jura-btn jura-btn-secondary" type="submit">OK</button>
          </form>
        </td>
        <td>
          <form method="post" style="display:inline;margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_menu">
            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
            <button class="jura-btn" type="submit" style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3"
              onclick="return confirm('Видалити меню «<?= e($m['name']) ?>» разом з усіма пунктами?')">Видалити</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin:.8rem 0 0;font-size:13px;color:#888">Щоб призначити меню для хедера або футера — змініть налаштування
    <a href="/admin/settings">Налаштування → «Меню хедера» / «Меню футера»</a>.</p>
</section>
<?php endif; ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Створити меню</h2>
  <form method="post" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create_menu">
    <div>
      <label class="jura-label">Код (slug)</label>
      <input class="jura-input" name="code" placeholder="main" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Назва</label>
      <input class="jura-input" name="name" placeholder="Головне меню" style="margin:0" required>
    </div>
    <button class="jura-btn jura-btn-primary" type="submit">Створити</button>
  </form>
</section>

<?php if (!empty($menus)): ?>
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Додати пункт меню</h2>
  <form method="post" id="add-item-form">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_item">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;align-items:flex-end">
      <div>
        <label class="jura-label">Меню</label>
        <select class="jura-input" name="menu_id" style="margin:0">
          <?php foreach ($menus as $m): ?>
            <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="jura-label">Сторінка</label>
        <select class="jura-input" id="page-picker" style="margin:0" onchange="menuPickPage(this)">
          <option value="">— вибрати зі списку —</option>
          <?php foreach ($quickLinks as $url => $label): ?>
            <option value="<?= e($url) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
          <option value="__custom__">Ввести вручну...</option>
        </select>
      </div>
      <div>
        <label class="jura-label">Назва пункту <span style="color:red">*</span></label>
        <input class="jura-input" name="title" id="item-title" placeholder="Назва в меню" style="margin:0" required>
      </div>
      <div>
        <label class="jura-label">URL</label>
        <input class="jura-input" name="url" id="item-url" placeholder="/about" style="margin:0" required>
      </div>
      <div>
        <label class="jura-label">Сортування</label>
        <input class="jura-input" name="sort_order" type="number" value="0" style="max-width:80px;margin:0">
      </div>
      <div>
        <label class="jura-label">Статус</label>
        <select class="jura-input" name="status" style="margin:0">
          <option value="active">Активний</option>
          <option value="hidden">Прихований</option>
        </select>
      </div>
      <div>
        <button class="jura-btn jura-btn-primary" type="submit">Додати</button>
      </div>
    </div>
  </form>
  <script>
  function menuPickPage(sel) {
    var val = sel.value;
    if (!val || val === '__custom__') { return; }
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('item-url').value = val;
    if (!document.getElementById('item-title').value) {
      document.getElementById('item-title').value = opt.dataset.label || '';
    }
  }
  </script>
</section>
<?php endif; ?>

<section class="jura-card">
  <h2 style="margin-top:0">Пункти меню</h2>
  <?php if (empty($items)): ?>
    <p style="color:#888">Пунктів меню ще немає. Скористайтесь формою вище.</p>
  <?php else:
    $grouped = [];
    foreach ($items as $i) {
        $grouped[$i['menu_name']][] = $i;
    }
    foreach ($grouped as $menuName => $menuItems): ?>
    <h3 style="margin:1.2rem 0 .5rem;font-size:15px;color:var(--jura-text-muted,#64748b)"><?= e($menuName) ?></h3>
    <table class="jura-table" style="margin-bottom:1rem">
      <thead>
        <tr><th>Назва</th><th>URL</th><th>Сорт.</th><th>Дії</th></tr>
      </thead>
      <tbody>
      <?php foreach ($menuItems as $i): ?>
        <tr>
          <td>
            <form method="post" style="display:flex;gap:.4rem;align-items:center;margin:0" id="mi-<?= (int) $i['id'] ?>">
              <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="edit_item">
              <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
              <input class="jura-input" name="title" value="<?= e($i['title']) ?>" style="width:150px;margin:0">
            </form>
          </td>
          <td>
            <input form="mi-<?= (int) $i['id'] ?>" class="jura-input" name="url" value="<?= e($i['url']) ?>" style="width:140px;margin:0">
          </td>
          <td>
            <input form="mi-<?= (int) $i['id'] ?>" class="jura-input" type="number" name="sort_order" value="<?= (int) $i['sort_order'] ?>" style="width:60px;margin:0">
          </td>
          <td style="white-space:nowrap;display:flex;gap:.35rem;align-items:center">
            <button form="mi-<?= (int) $i['id'] ?>" class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .6rem">✓</button>
            <form method="post" style="display:inline;margin:0">
              <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_item">
              <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
              <button class="jura-btn" type="submit" onclick="return confirm('Видалити?')"
                style="padding:.3rem .6rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3">✕</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
