<?php
global $pdo;
$page = $page ?? [];
$settings = $settings ?? [];
$blocks = $blocks ?? [];

// A page built from the block constructor (see App\Core\BlockRegistry and
// the "Блоки сторінки" editor on /admin/pages) fully owns its own markup --
// render exactly what's configured and stop. A home page with zero blocks
// (not migrated yet, or a fresh install) falls through to the classic
// hardcoded layout below so nothing goes blank.
if (!empty($blocks)) {
    foreach ($blocks as $block) {
        echo \App\Core\BlockRegistry::render((string) $block['block_type'], $block['settings'], $pdo);
    }
    return;
}

$latestPosts = [];
if (isset($pdo)) {
    try {
        $latestPosts = $pdo->query('SELECT * FROM ' . jura_table('posts') . " WHERE status='published' ORDER BY published_at DESC, id DESC LIMIT 3")->fetchAll();
    } catch (\Throwable) {}
}
?>
<section class="hero">
  <div class="site-container">
    <span class="hero__eyebrow">Ласкаво просимо</span>
    <h1><?= e($page['title'] ?? ($settings['site_name'] ?? 'Jura CMS')) ?></h1>
    <?php if (!empty($page['excerpt'])): ?>
    <p><?= e($page['excerpt']) ?></p>
    <?php endif; ?>
    <div class="hero__actions">
      <a class="btn btn-primary" href="/blog">Читати блог</a>
      <a class="btn btn-secondary" href="/contacts">Зв'язатися з нами</a>
    </div>
  </div>
</section>

<?php if (!empty($page['content'])): ?>
<section class="section">
  <div class="site-container card page-content">
    <?= $page['content'] ?>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="site-container">
    <h2 class="section-title">Все необхідне для сайту</h2>
    <p class="section-subtitle">Jura CMS з коробки: сторінки, блог, медіатека та меню — керуйте всім з однієї адмін-панелі.</p>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-card__icon">📄</div>
        <h3>Сторінки</h3>
        <p>Довільні сторінки з SEO-полями, шаблонами та власними роутами.</p>
      </div>
      <div class="feature-card">
        <div class="feature-card__icon">📰</div>
        <h3>Блог</h3>
        <p>Публікації з категоріями, зображеннями та пагінацією з коробки.</p>
      </div>
      <div class="feature-card">
        <div class="feature-card__icon">🖼️</div>
        <h3>Медіатека</h3>
        <p>Завантаження та керування зображеннями й файлами сайту.</p>
      </div>
      <div class="feature-card">
        <div class="feature-card__icon">🧭</div>
        <h3>Меню та редіректи</h3>
        <p>Гнучке керування навігацією, 301/302 редіректами та маршрутами.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="site-container card">
    <div class="stats-row">
      <div class="stat"><strong>100%</strong><span>Open source</span></div>
      <div class="stat"><strong>&lt;5 хв</strong><span>Час установки</span></div>
      <div class="stat"><strong>2</strong><span>Готові теми (адмін + фронт)</span></div>
      <div class="stat"><strong>∞</strong><span>Сторінок і публікацій</span></div>
    </div>
  </div>
</section>

<?php if (!empty($latestPosts)): ?>
<section class="section">
  <div class="site-container">
    <h2 class="section-title">Останні публікації</h2>
    <div class="post-grid">
      <?php foreach ($latestPosts as $post): ?>
      <article class="post-card">
        <?php if (!empty($post['featured_image'])): ?>
        <div class="post-card__image"><img src="/public/userfiles/posts/<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></div>
        <?php endif; ?>
        <div class="post-card__body">
          <?php if (!empty($post['published_at'])): ?><span class="post-card__date"><?= e(substr((string) $post['published_at'], 0, 10)) ?></span><?php endif; ?>
          <h3 class="post-card__title"><a href="/blog/<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
          <?php if (!empty($post['excerpt'])): ?><p class="post-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
          <a class="post-card__more" href="/blog/<?= e($post['slug']) ?>">Читати далі →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $featuredRooms = $featured_rooms ?? []; if (!empty($featuredRooms)): ?>
<section class="section">
  <div class="site-container">
    <h2 class="section-title">Номери</h2>
    <div class="post-grid">
      <?php foreach ($featuredRooms as $room): ?>
      <article class="post-card">
        <div class="post-card__body">
          <h3 class="post-card__title"><a href="/rooms/<?= e($room['slug']) ?>"><?= e($room['title']) ?></a></h3>
          <?php if (!empty($room['excerpt'])): ?><p class="post-card__excerpt"><?= e($room['excerpt']) ?></p><?php endif; ?>
          <a class="post-card__more" href="/rooms/<?= e($room['slug']) ?>">Докладніше →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $featuredPromotions = $featured_promotions ?? []; if (!empty($featuredPromotions)): ?>
<section class="section">
  <div class="site-container">
    <h2 class="section-title">Акції</h2>
    <div class="post-grid">
      <?php foreach ($featuredPromotions as $promo): ?>
      <article class="post-card">
        <div class="post-card__body">
          <h3 class="post-card__title"><a href="/promotions/<?= e($promo['slug']) ?>"><?= e($promo['title']) ?></a></h3>
          <?php if (!empty($promo['excerpt'])): ?><p class="post-card__excerpt"><?= e($promo['excerpt']) ?></p><?php endif; ?>
          <a class="post-card__more" href="/promotions/<?= e($promo['slug']) ?>">Докладніше →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="site-container">
    <div class="cta-banner">
      <h2>Готові почати?</h2>
      <p>Увійдіть в адмін-панель і налаштуйте сайт під себе — сторінки, меню, контакти та вигляд.</p>
      <a class="btn btn-on-dark" href="/admin">Відкрити адмін-панель</a>
    </div>
  </div>
</section>
