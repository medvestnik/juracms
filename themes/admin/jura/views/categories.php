<?php
$categories = $categories ?? [];
$allLocales = $all_locales ?? [];

// Group every row by its translation root so each root can show, inline, a
// "+code" button for every active locale it doesn't have a translation in
// yet -- same pattern as the post editor's translations block.
$byRoot = [];
foreach ($categories as $c) {
    $rootId = (int) ($c['translation_of'] ?: $c['id']);
    $byRoot[$rootId][(string) $c['locale']] = $c;
}
?>

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
    <?php if (!empty($allLocales)): ?>
    <div>
      <label class="jura-label">Мова</label>
      <select class="jura-input" name="locale" style="margin:0">
        <?php foreach ($allLocales as $l): ?>
          <option value="<?= e($l['code']) ?>"><?= e($l['native_name']) ?> (<?= e($l['code']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
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
    <thead><tr><th>Назва</th><th>Slug</th><th>Опис</th><th>Мова</th><th>Сортування</th><th>Публікацій</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): $rootId = (int) ($c['translation_of'] ?: $c['id']); ?>
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
      <td style="font-size:.82rem">
        <span style="display:inline-block;padding:.15rem .5rem;border-radius:999px;background:#eef2ff;color:#4338ca;font-weight:600"><?= e($c['locale'] ?: '—') ?></span>
        <?php if (empty($c['translation_of'])): ?>
          <?php if (!empty($allLocales)): ?>
          <!-- Only the root row offers "+locale" buttons -- with a translate
               button on every sibling row too, the same action was reachable
               from several places at once and double-clicking across rows
               created duplicate translations. One place, one button each. -->
          <div style="display:flex;gap:.25rem;flex-wrap:wrap;margin-top:.35rem">
            <?php foreach ($allLocales as $l): $code = $l['code']; if (isset($byRoot[$rootId][$code])) continue; ?>
            <form method="post" style="margin:0">
              <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="translate">
              <input type="hidden" name="id" value="<?= $rootId ?>">
              <input type="hidden" name="locale" value="<?= e($code) ?>">
              <button class="jura-btn" type="submit" title="Створити переклад (<?= e($code) ?>)"
                onclick="this.disabled=true;this.form.submit()"
                style="padding:.1rem .4rem;font-size:.72rem;background:#f8fafc;color:#475569;border:1px dashed #cbd5e1">+<?= e($code) ?></button>
            </form>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php else: ?>
          <div style="margin-top:.35rem;color:#94a3b8;font-size:.72rem">переклад #<?= $rootId ?></div>
        <?php endif; ?>
      </td>
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
