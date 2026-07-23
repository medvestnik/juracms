<?php
$actions = [
    'fix_duplicates' => ['Видалити дублікати сторінок', 'Залишає першу сторінку з кожним slug, видаляє дублікати без прив\'язаного роуту.'],
    'fix_templates'  => ['Виправити шаблони системних сторінок', 'Приводить template сторінок home/contacts у відповідність до їх slug.'],
    'rebuild_routes' => ['Відновити роути базових сторінок', 'Перестворює роути для home ("/"), about, contacts, blog за їхніми slug.'],
    'rebuild_menu'   => ['Перебудувати головне меню', 'Очищає меню "main" і заповнює його базовими пунктами: Головна, Блог, Контакти.'],
    'refresh_demo_content' => ['Оновити демо-текст сторінок', 'Перезаписує вміст сторінок home/about/contacts актуальним демо-текстом.'],
];
?>
<?php if (!empty($flash_success)): ?>
<div class="jura-alert" style="margin-bottom:1rem;white-space:pre-line"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239;white-space:pre-line"><?= e($flash_error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem">
  <?php foreach ($actions as $action => [$label, $desc]): ?>
  <section class="jura-card" style="display:flex;flex-direction:column;gap:.6rem">
    <div>
      <strong><?= e($label) ?></strong>
      <p style="font-size:.85rem;color:#64748b;margin:.35rem 0 0"><?= e($desc) ?></p>
    </div>
    <form method="post" style="margin-top:auto">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="<?= e($action) ?>">
      <button class="jura-btn jura-btn-secondary" type="submit">Виконати</button>
    </form>
  </section>
  <?php endforeach; ?>
</div>
