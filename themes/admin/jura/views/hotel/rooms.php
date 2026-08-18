<?php $rooms = $rooms ?? []; ?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
  <a href="/admin/hotel/rooms/create" class="jura-btn jura-btn-primary">+ Додати номер</a>
</div>
<section class="jura-card">
  <?php if (empty($rooms)): ?>
    <p style="color:#888">Номерів ще немає. Натисніть «+ Додати номер».</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Назва</th><th>Slug</th><th>Ціна від</th><th>Статус</th><th>Сорт.</th><th>Дії</th></tr></thead>
    <tbody>
    <?php foreach ($rooms as $room): ?>
      <tr>
        <td><?= e($room['title']) ?></td>
        <td><code><?= e($room['slug']) ?></code></td>
        <td><?= e($room['price_from']) ?> <?= e($room['currency']) ?></td>
        <td>
          <form method="post" action="/admin/hotel/rooms/<?= $room['id'] ?>/toggle" style="margin:0;display:inline">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="jura-btn" style="padding:.25rem .6rem;font-size:.78rem;<?= $room['status']==='published' ? 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7' : 'background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db' ?>">
              <?= $room['status']==='published' ? '✓ Опубл.' : '○ Чернетка' ?>
            </button>
          </form>
        </td>
        <td><?= (int)$room['sort_order'] ?></td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;align-items:center">
          <a class="jura-btn jura-btn-secondary" href="/admin/hotel/rooms/<?= $room['id'] ?>/edit" style="padding:.3rem .65rem">Редагувати</a>
          <a class="jura-btn jura-btn-secondary" href="/rooms/<?= e($room['slug']) ?>" target="_blank" rel="noopener" style="padding:.3rem .6rem">↗</a>
          <form method="post" action="/admin/hotel/rooms/<?= $room['id'] ?>/delete" style="display:inline;margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="jura-btn" onclick="return confirm('Видалити «<?= e($room['title']) ?>»?')"
              style="padding:.3rem .6rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3">✕ Видалити</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
