<?php $post = $post ?? []; ?>
<section class="section">
  <div class="site-container" style="max-width:780px">
    <a class="back-link" href="/blog">&larr; Усі публікації</a>
    <article>
      <header class="post-header">
        <?php if (!empty($post['published_at'])): ?>
        <div class="post-header__date"><?= e(substr((string) $post['published_at'], 0, 10)) ?></div>
        <?php endif; ?>
        <h1><?= e($post['title'] ?? '') ?></h1>
      </header>
      <?php if (!empty($post['featured_image'])): ?>
      <div class="post-cover">
        <img src="/public/userfiles/posts/<?= e($post['featured_image']) ?>" alt="<?= e($post['title'] ?? '') ?>">
      </div>
      <?php endif; ?>
      <div class="post-body">
        <?= $post['content'] ?? '' ?>
      </div>
    </article>
  </div>
</section>
