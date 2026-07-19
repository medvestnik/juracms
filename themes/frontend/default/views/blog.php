<?php
$posts = $posts ?? [];
$page = $page ?? [];
$pagination = $pagination ?? ['current' => 1, 'total' => 1, 'per_page' => 12];
?>
<section class="section">
  <div class="site-container">
    <h1 class="section-title"><?= e($page['title'] ?? 'Блог') ?></h1>

    <?php if (empty($posts)): ?>
    <div class="empty-state">Публікацій ще немає.</div>
    <?php else: ?>
    <div class="post-grid">
      <?php foreach ($posts as $post): ?>
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

    <?php if (($pagination['total'] ?? 1) > 1): $cur = (int) $pagination['current']; $total = (int) $pagination['total']; ?>
    <nav class="pagination">
      <?php if ($cur > 1): ?><a href="?page=<?= $cur - 1 ?>">&larr;</a><?php endif; ?>
      <?php for ($p = 1; $p <= $total; $p++): ?>
        <?php if ($p === $cur): ?><span class="is-active"><?= $p ?></span><?php else: ?><a href="?page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($cur < $total): ?><a href="?page=<?= $cur + 1 ?>">&rarr;</a><?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
