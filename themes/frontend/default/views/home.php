<?php
global $pdo;
$page = $page ?? [];
$settings = $settings ?? [];
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
