<?php $edit = $edit ?? null; ?>
<?php if ($edit === null):
  $sort        = $sort ?? 'order';
  $dir         = $dir ?? 'ASC';
  $perPage     = $per_page ?? 20;
  $perPageOpts = $per_page_opts ?? [20,25,30,50,100,200];
  $curPage     = $cur_page ?? 1;
  $totalPages  = $total_pages ?? 1;
  $total       = $total ?? 0;

  $qBase = ['sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage,'page'=>$curPage];
  $urlWith = function(array $overrides) use ($qBase): string {
      return '?' . http_build_query(array_merge($qBase, $overrides));
  };
  $sortDirFor = function(string $col) use ($sort, $dir): string {
      if ($col === 'order') return 'asc';
      return ($sort === $col && $dir === 'DESC') ? 'asc' : 'desc';
  };
  $sortIcon = function(string $col) use ($sort, $dir): string {
      if ($sort !== $col) return '<span style="color:#cbd5e1">↕</span>';
      return $dir === 'ASC' ? '↑' : '↓';
  };
  $pageUrl = function(int $pg) use ($sort, $dir, $perPage): string {
      return '?sort='.$sort.'&dir='.strtolower($dir).'&per_page='.$perPage.'&page='.$pg;
  };
endif; ?>
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
          <?php foreach (($template_options ?? ['page' => 'Сторінка']) as $val => $label): ?>
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
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
  <a href="/admin/pages/create" class="jura-btn jura-btn-primary">+ Додати сторінку</a>
  <div style="display:flex;align-items:center;gap:.4rem;font-size:.83rem;color:#64748b;margin-left:.5rem">
    Сортування:
    <?php foreach (['order'=>'Вручну','date'=>'За датою','title'=>'За назвою','id'=>'За ID'] as $col => $label): ?>
    <a href="<?= $urlWith(['sort'=>$col,'dir'=>$sortDirFor($col),'page'=>1]) ?>"
       class="jura-btn jura-btn-secondary"
       style="padding:.25rem .55rem;font-size:.8rem<?= $sort===$col ? ';font-weight:700'.($col==='order'?';background:#fffbeb;border-color:#fbbf24':'') : '' ?>"
       <?= $col==='order' ? 'title="Перетягніть рядки щоб змінити порядок"' : '' ?>>
      <?= $label ?> <?= $sortIcon($col) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <form method="get" style="display:flex;align-items:center;gap:.35rem;font-size:.83rem;margin-left:auto">
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e(strtolower($dir)) ?>">
    <input type="hidden" name="page" value="1">
    <label style="color:#64748b">Записів:</label>
    <select name="per_page" class="jura-input" style="padding:.2rem .4rem;font-size:.83rem;width:auto" onchange="this.form.submit()">
      <?php foreach ($perPageOpts as $opt): ?>
      <option value="<?= $opt ?>" <?= $opt===$perPage ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<section class="jura-card" style="overflow:auto">
  <?php if (empty($pages)): ?>
  <p style="color:#888">Сторінок ще немає.</p>
  <?php else: ?>
  <table class="jura-table" id="pages-table">
    <thead>
      <tr>
        <?php if ($sort === 'order'): ?><th style="width:32px"></th><?php endif; ?>
        <th style="width:44px">ID</th>
        <th>Заголовок</th>
        <th>URL</th>
        <th>Статус</th>
        <th>Шаблон</th>
        <th>Оновлено</th>
        <th style="text-align:center">Дії</th>
      </tr>
    </thead>
    <tbody id="pages-tbody">
    <?php foreach ($pages as $p): ?>
      <tr data-id="<?= (int) $p['id'] ?>">
        <?php if ($sort === 'order'): ?>
        <td style="cursor:grab;color:#94a3b8;text-align:center;font-size:1.1rem;user-select:none" class="drag-handle">⠿</td>
        <?php endif; ?>
        <td style="color:#94a3b8;font-size:.8rem"><?= (int) $p['id'] ?></td>
        <td><?= e($p['title']) ?></td>
        <td><code><?= e($p['route_path'] ?? '') ?></code></td>
        <td>
          <?php $statusLabels = ['published' => ['Опубліковано', '#d1fae5', '#065f46', '#6ee7b7'], 'draft' => ['Чернетка', '#f3f4f6', '#6b7280', '#d1d5db'], 'hidden' => ['Прихована', '#fef3c7', '#92400e', '#fde68a']];
          $sl = $statusLabels[$p['status']] ?? $statusLabels['draft']; ?>
          <span style="font-size:.72rem;background:<?= $sl[1] ?>;color:<?= $sl[2] ?>;border:1px solid <?= $sl[3] ?>;border-radius:20px;padding:.1rem .5rem"><?= $sl[0] ?></span>
        </td>
        <td><?= e($p['template'] ?? 'page') ?></td>
        <td style="font-size:.82rem;color:#64748b"><?= e($p['updated_at'] ?? '') ?></td>
        <td style="white-space:nowrap;display:flex;gap:.4rem;align-items:center;justify-content:flex-end">
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

  <?php if ($totalPages > 1): ?>
  <nav style="display:flex;align-items:center;gap:.4rem;margin-top:1rem;flex-wrap:wrap">
    <?php if ($curPage > 1): ?>
    <a href="<?= $pageUrl($curPage-1) ?>" class="jura-btn jura-btn-secondary" style="padding:.3rem .65rem">&larr;</a>
    <?php endif; ?>
    <?php
    $pStart = max(1, $curPage-3);
    $pEnd   = min($totalPages, $curPage+3);
    if ($pStart > 1): ?>
      <a href="<?= $pageUrl(1) ?>" class="jura-btn jura-btn-secondary" style="padding:.3rem .55rem">1</a>
      <?php if ($pStart > 2): ?><span style="color:#94a3b8">…</span><?php endif; ?>
    <?php endif; ?>
    <?php for ($pg = $pStart; $pg <= $pEnd; $pg++): ?>
    <a href="<?= $pageUrl($pg) ?>" class="jura-btn <?= $pg===$curPage?'jura-btn-primary':'jura-btn-secondary' ?>" style="padding:.3rem .55rem"><?= $pg ?></a>
    <?php endfor; ?>
    <?php if ($pEnd < $totalPages): ?>
      <?php if ($pEnd < $totalPages-1): ?><span style="color:#94a3b8">…</span><?php endif; ?>
      <a href="<?= $pageUrl($totalPages) ?>" class="jura-btn jura-btn-secondary" style="padding:.3rem .55rem"><?= $totalPages ?></a>
    <?php endif; ?>
    <?php if ($curPage < $totalPages): ?>
    <a href="<?= $pageUrl($curPage+1) ?>" class="jura-btn jura-btn-secondary" style="padding:.3rem .65rem">&rarr;</a>
    <?php endif; ?>
    <span style="font-size:.8rem;color:#94a3b8;margin-left:.5rem">Всього: <?= (int) $total ?></span>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>

<?php if ($sort === 'order'): ?>
<div id="order-saved" style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:8px;padding:.6rem 1.1rem;font-size:.88rem;z-index:9999">✓ Порядок збережено</div>
<script>
(function(){
  var tbody = document.getElementById('pages-tbody');
  if (!tbody) return;
  var dragging = null;
  Array.from(tbody.querySelectorAll('tr[data-id]')).forEach(function(row){ row.setAttribute('draggable','true'); });
  tbody.addEventListener('dragstart', function(e){
    var row = e.target.closest('tr'); if(!row) return;
    dragging = row; row.style.opacity='0.5'; e.dataTransfer.effectAllowed='move';
  });
  tbody.addEventListener('dragend', function(e){
    var row = e.target.closest('tr'); if(row) row.style.opacity=''; dragging=null;
  });
  tbody.addEventListener('dragover', function(e){
    e.preventDefault();
    var row = e.target.closest('tr'); if(!row||row===dragging) return;
    var after = e.clientY > row.getBoundingClientRect().top + row.getBoundingClientRect().height/2;
    tbody.insertBefore(dragging, after ? row.nextSibling : row);
  });
  tbody.addEventListener('drop', function(e){
    e.preventDefault();
    var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(r){return r.dataset.id;});
    fetch('/admin/pages/reorder',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'_token=<?= e(csrf_token()) ?>&ids='+encodeURIComponent(JSON.stringify(ids))
    }).then(function(r){return r.json();}).then(function(d){
      if(d.ok){var el=document.getElementById('order-saved');el.style.display='block';setTimeout(function(){el.style.display='none';},2000);}
    });
  });
})();
</script>
<?php endif; ?>
<?php endif; ?>
