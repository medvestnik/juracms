<?php $locales = $locales ?? []; ?>
<?php if (!empty($flash_error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239"><?= e($flash_error) ?></div>
<?php endif; ?>

<p style="color:#64748b;font-size:.9rem;margin:0 0 1rem">
  Мова за замовчуванням завжди активна і доступна без префікса в URL (наприклад, <code>/about</code>).
  Інші активні мови отримують URL з префіксом коду (наприклад, <code>/ru/about</code>).
  Переклади сторінок і публікацій додаються на сторінці редагування відповідної сторінки/публікації.
</p>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Додати мову</h2>
  <form method="post" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <div>
      <label class="jura-label">Код (ISO 639-1)</label>
      <input class="jura-input" name="code" placeholder="de" style="margin:0;max-width:100px" maxlength="16" required>
    </div>
    <div>
      <label class="jura-label">Назва (англ.)</label>
      <input class="jura-input" name="name" placeholder="German" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Назва рідною мовою</label>
      <input class="jura-input" name="native_name" placeholder="Deutsch" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Сортування</label>
      <input class="jura-input" type="number" name="sort_order" value="<?= count($locales) ?>" style="max-width:80px;margin:0">
    </div>
    <button class="jura-btn jura-btn-primary" type="submit">Додати</button>
  </form>
</section>

<section class="jura-card">
  <h2 style="margin-top:0">Мови сайту</h2>
  <table class="jura-table">
    <thead><tr><th>Код</th><th>Назва</th><th>Рідною мовою</th><th>Сортування</th><th>Активна</th><th>За замовчуванням</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($locales as $l): ?>
    <tr>
      <td>
        <code><?= e($l['code']) ?></code>
        <form method="post" id="loc-<?= (int) $l['id'] ?>" style="display:none">
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
        </form>
      </td>
      <td><input form="loc-<?= (int) $l['id'] ?>" class="jura-input" name="name" value="<?= e($l['name']) ?>" style="width:150px;margin:0"></td>
      <td><input form="loc-<?= (int) $l['id'] ?>" class="jura-input" name="native_name" value="<?= e($l['native_name']) ?>" style="width:150px;margin:0"></td>
      <td><input form="loc-<?= (int) $l['id'] ?>" class="jura-input" type="number" name="sort_order" value="<?= (int) $l['sort_order'] ?>" style="width:70px;margin:0"></td>
      <td>
        <?php if ((int) $l['is_default'] === 1): ?>
        <input type="checkbox" checked disabled title="Мова за замовчуванням завжди активна">
        <?php else: ?>
        <input form="loc-<?= (int) $l['id'] ?>" type="checkbox" name="is_active" value="1" <?= (int) $l['is_active'] === 1 ? 'checked' : '' ?>>
        <?php endif; ?>
      </td>
      <td>
        <?php if ((int) $l['is_default'] === 1): ?>
        <span style="font-size:.72rem;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:20px;padding:.1rem .5rem">✓ За замовчуванням</span>
        <?php else: ?>
        <form method="post" style="margin:0">
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="set_default">
          <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
          <button class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .6rem;font-size:.8rem">Зробити основною</button>
        </form>
        <?php endif; ?>
      </td>
      <td style="white-space:nowrap;display:flex;gap:.4rem">
        <button form="loc-<?= (int) $l['id'] ?>" class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .6rem;font-size:.8rem">✓</button>
        <?php if ((int) $l['is_default'] !== 1): ?>
        <form method="post" style="margin:0">
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
          <button class="jura-btn" type="submit" style="padding:.3rem .6rem;font-size:.8rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3"
            onclick="return confirm('Видалити мову «<?= e($l['name']) ?>»? Існуючі переклади не будуть видалені, але стануть недоступні за URL з цим префіксом.')">✕</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
