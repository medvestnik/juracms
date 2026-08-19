<?php $promotions = $promotions ?? []; ?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
  <a href="/admin/hotel/promotions/create" class="jura-btn jura-btn-primary">+ Додати акцію</a>
</div>
<section class="jura-card">
  <?php if (empty($promotions)): ?>
    <p style="color:#888">Акцій ще немає.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Назва</th><th>Slug</th><th>Статус</th><th>Сортування</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($promotions as $p): ?>
      <tr>
        <td><?= e($p['title']) ?></td>
        <td><code><?= e($p['slug']) ?></code></td>
        <td><?= e($p['status']) ?></td>
        <td><?= e($p['sort_order']) ?></td>
        <td style="white-space:nowrap">
          <a class="jura-btn jura-btn-secondary" href="/admin/hotel/promotions/<?= $p['id'] ?>/edit">Редагувати</a>
          <a class="jura-btn jura-btn-secondary" href="/promotions/<?= e($p['slug']) ?>" target="_blank" rel="noopener">Переглянути</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
