<?php
$posts       = $posts ?? [];
$sort        = $sort ?? 'date';
$dir         = $dir ?? 'DESC';
$perPage     = $per_page ?? 20;
$perPageOpts = $per_page_opts ?? [20,25,30,50,100,200];
$curPage     = $cur_page ?? 1;
$totalPages  = $total_pages ?? 1;
$total       = $total ?? 0;

$sortMap = ['date'=>'За датою','title'=>'За назвою','id'=>'За ID','order'=>'Вручну'];

// Build a query string with specific overrides
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
?>
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
  <a href="/admin/posts/create" class="jura-btn jura-btn-primary">+ Додати публікацію</a>
  <div style="display:flex;align-items:center;gap:.4rem;font-size:.83rem;color:#64748b;margin-left:.5rem">
    Сортування:
    <?php foreach (['date'=>'За датою','title'=>'За назвою','id'=>'За ID','order'=>'Вручну'] as $col => $label): ?>
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
  <?php if (empty($posts)): ?>
    <p style="color:#888">Публікацій ще немає.</p>
  <?php else: ?>
  <table class="jura-table" id="posts-table">
    <thead>
      <tr>
        <?php if ($sort === 'order'): ?><th style="width:32px"></th><?php endif; ?>
        <th style="width:44px">ID</th>
        <th>Назва</th>
        <th>Статус</th>
        <th>Опубліковано</th>
        <th>Дії</th>
      </tr>
    </thead>
    <tbody id="posts-tbody">
    <?php foreach ($posts as $p): ?>
      <tr data-id="<?= (int)$p['id'] ?>">
        <?php if ($sort === 'order'): ?>
        <td style="cursor:grab;color:#94a3b8;text-align:center;font-size:1.1rem;user-select:none" class="drag-handle">⠿</td>
        <?php endif; ?>
        <td style="color:#94a3b8;font-size:.8rem"><?= (int)$p['id'] ?></td>
        <td><?= e($p['title']) ?></td>
        <td>
          <form method="post" action="/admin/posts/<?= (int)$p['id'] ?>/toggle" style="margin:0;display:inline">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="jura-btn" style="padding:.25rem .6rem;font-size:.78rem;<?= $p['status']==='published' ? 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7' : 'background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db' ?>">
              <?= $p['status']==='published' ? '✓ Опубл.' : '○ Чернетка' ?>
            </button>
          </form>
        </td>
        <td style="font-size:.82rem;color:#64748b"><?= e(substr($p['published_at'] ?? '—', 0, 10)) ?></td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;align-items:center">
          <a class="jura-btn jura-btn-secondary" href="/admin/posts/<?= (int)$p['id'] ?>/edit" style="padding:.3rem .65rem">Редагувати</a>
          <form method="post" action="/admin/posts/<?= (int)$p['id'] ?>/delete" style="display:inline;margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button class="jura-btn" type="submit" onclick="return confirm('Видалити «<?= e($p['title']) ?>»?')"
              style="padding:.3rem .6rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3">✕</button>
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
    <span style="font-size:.8rem;color:#94a3b8;margin-left:.5rem">Всього: <?= (int)$total ?></span>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>

<?php if ($sort === 'order'): ?>
<div id="order-saved" style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:8px;padding:.6rem 1.1rem;font-size:.88rem;z-index:9999">✓ Порядок збережено</div>
<script>
(function(){
  var tbody = document.getElementById('posts-tbody');
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
    fetch('/admin/posts/reorder',{
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
