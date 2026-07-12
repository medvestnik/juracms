<?php $redirects = $redirects ?? []; ?>

<section class="jura-card" style="margin-bottom:1.25rem">
  <h2 style="margin-top:0">Додати редірект</h2>
  <form method="post" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="jura-label">Звідки</label>
      <input class="jura-input" name="source_path" placeholder="/old-page" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Куди</label>
      <input class="jura-input" name="target_path" placeholder="/new-page" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Код</label>
      <select class="jura-input" name="status_code" style="margin:0">
        <option value="301">301 (постійний)</option>
        <option value="302">302 (тимчасовий)</option>
      </select>
    </div>
    <label style="display:flex;align-items:center;gap:.4rem;margin-bottom:.6rem">
      <input type="checkbox" name="is_active" value="1" checked> Активний
    </label>
    <button class="jura-btn jura-btn-primary" type="submit">Додати</button>
  </form>
</section>

<section class="jura-card">
  <?php if (empty($redirects)): ?>
  <p style="color:#888">Редіректів ще немає.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>Звідки</th><th>Куди</th><th>Код</th><th>Статус</th><th>Переходів</th><th style="width:70px">Дії</th></tr></thead>
    <tbody>
    <?php foreach ($redirects as $r): ?>
      <tr>
        <td><code><?= e($r['source_path']) ?></code></td>
        <td><code><?= e($r['target_path']) ?></code></td>
        <td><?= (int) $r['status_code'] ?></td>
        <td><?= ((int) ($r['is_active'] ?? 0)) === 1 ? '✓ активний' : 'вимкнено' ?></td>
        <td><?= (int) ($r['hit_count'] ?? 0) ?></td>
        <td>
          <form method="post" style="margin:0" onsubmit="return confirm('Видалити редірект?')">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="jura-btn" type="submit" style="padding:.25rem .55rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3">✕</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
