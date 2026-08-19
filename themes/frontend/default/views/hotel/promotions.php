<?php $promotions = $promotions ?? []; ?>
<section class="section">
  <div class="site-container">
    <h1 class="section-title">Акції</h1>

    <?php if (empty($promotions)): ?>
    <div class="empty-state">Акції ще не додані.</div>
    <?php else: ?>
    <div class="post-grid">
      <?php foreach ($promotions as $promotion): ?>
      <article class="post-card">
        <div class="post-card__body">
          <h3 class="post-card__title"><a href="/promotions/<?= e($promotion['slug']) ?>"><?= e($promotion['title']) ?></a></h3>
          <?php if (!empty($promotion['excerpt'])): ?><p class="post-card__excerpt"><?= e($promotion['excerpt']) ?></p><?php endif; ?>
          <a class="post-card__more" href="/promotions/<?= e($promotion['slug']) ?>">Докладніше →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
