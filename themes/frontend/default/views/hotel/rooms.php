<?php $rooms = $rooms ?? []; ?>
<style>
/* Minimal, theme-independent styling so this module looks reasonable on
   any frontend theme out of the box. A theme can override these classes
   with its own design — this is only a fallback baseline. */
.hotel-price { font-weight: 700; color: var(--site-primary); font-size: .95rem; }
.hotel-meta { color: var(--site-text-muted); font-size: .85rem; }
</style>
<section class="section">
  <div class="site-container">
    <h1 class="section-title">Номери</h1>

    <?php if (empty($rooms)): ?>
    <div class="empty-state">Номери ще не додані.</div>
    <?php else: ?>
    <div class="post-grid">
      <?php foreach ($rooms as $room): ?>
      <article class="post-card">
        <?php if (!empty($room['feat_img_file'])): ?>
        <div class="post-card__image"><img src="/public/userfiles/rooms/<?= e($room['feat_img_file']) ?>" alt="<?= e($room['title']) ?>" loading="lazy"></div>
        <?php endif; ?>
        <div class="post-card__body">
          <?php if (!empty($room['area']) || !empty($room['capacity'])): ?>
          <span class="hotel-meta"><?= e($room['area'] ?? '') ?><?= (!empty($room['area']) && !empty($room['capacity'])) ? ' · ' : '' ?><?= e($room['capacity'] ?? '') ?></span>
          <?php endif; ?>
          <h3 class="post-card__title"><a href="/rooms/<?= e($room['slug']) ?>"><?= e($room['title']) ?></a></h3>
          <?php if (!empty($room['excerpt'])): ?><p class="post-card__excerpt"><?= e($room['excerpt']) ?></p><?php endif; ?>
          <span class="hotel-price">від <?= e($room['price_from']) ?> <?= e($room['currency']) ?></span>
          <a class="post-card__more" href="/rooms/<?= e($room['slug']) ?>">Докладніше →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
