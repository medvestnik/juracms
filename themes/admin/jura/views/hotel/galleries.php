<?php $galleries = $galleries ?? []; ?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
  <a href="/admin/hotel/galleries/create" class="jura-btn jura-btn-primary">+ Додати галерею</a>
</div>
<section class="jura-card">
  <?php if (empty($galleries)): ?>
    <p style="color:#888">Галерей ще немає.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Назва</th><th>Slug</th><th>Статус</th><th>Сортування</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($galleries as $g): ?>
      <tr>
        <td><?= e($g['title']) ?></td>
        <td><code><?= e($g['slug']) ?></code></td>
        <td><?= e($g['status']) ?></td>
        <td><?= e($g['sort_order']) ?></td>
        <td style="white-space:nowrap">
          <a class="jura-btn jura-btn-secondary" href="/admin/hotel/galleries/<?= $g['id'] ?>/edit">Редагувати</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
