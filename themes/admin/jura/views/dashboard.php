<?php $s = $stats ?? []; ?>
<div class="jura-grid jura-grid-4" style="margin-bottom:1.5rem">
  <a href="/admin/pages" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Сторінки</div>
    <span class="jura-stat-value"><?= (int) ($s['pages'] ?? 0) ?></span>
  </a>
  <a href="/admin/posts" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Публікації</div>
    <span class="jura-stat-value"><?= (int) ($s['posts'] ?? 0) ?></span>
  </a>
  <a href="/admin/media" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Медіа</div>
    <span class="jura-stat-value"><?= (int) ($s['media_files'] ?? 0) ?></span>
  </a>
  <a href="/admin/users" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Користувачі</div>
    <span class="jura-stat-value"><?= (int) ($s['users'] ?? 0) ?></span>
  </a>
</div>

<div class="jura-grid jura-grid-4" style="margin-bottom:1.5rem">
  <a href="/admin/redirects" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Редіректи</div>
    <span class="jura-stat-value"><?= (int) ($s['redirects'] ?? 0) ?></span>
  </a>
  <a href="/admin/menus" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Пункти меню</div>
    <span class="jura-stat-value"><?= (int) ($s['menu_items'] ?? 0) ?></span>
  </a>
  <div class="jura-card">
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Заявки</div>
    <span class="jura-stat-value"><?= (int) ($s['leads'] ?? 0) ?></span>
  </div>
</div>

<div class="jura-grid jura-grid-2">
  <section class="jura-card">
    <h2 class="jura-card-title" style="margin-bottom:1rem">Швидкі дії</h2>
    <div style="display:flex;flex-wrap:wrap;gap:.6rem">
      <a href="/admin/pages/create" class="jura-btn jura-btn-primary">+ Нова сторінка</a>
      <a href="/admin/posts/create" class="jura-btn jura-btn-secondary">+ Нова публікація</a>
      <a href="/admin/media" class="jura-btn jura-btn-secondary">Медіатека</a>
      <a href="/admin/menus" class="jura-btn jura-btn-secondary">Меню</a>
      <a href="/admin/settings" class="jura-btn jura-btn-secondary">Налаштування</a>
    </div>
  </section>
  <section class="jura-card">
    <h2 class="jura-card-title" style="margin-bottom:1rem">Сторінки сайту</h2>
    <div style="display:grid;gap:.6rem">
      <a href="/" target="_blank" rel="noopener" style="font-size:.875rem">↗ Головна</a>
      <a href="/blog" target="_blank" rel="noopener" style="font-size:.875rem">↗ Блог</a>
      <a href="/contacts" target="_blank" rel="noopener" style="font-size:.875rem">↗ Контакти</a>
      <a href="/admin/updates" style="font-size:.875rem">⚙ Оновлення та міграції</a>
    </div>
  </section>
</div>
