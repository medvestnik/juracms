<?php $galleries = $galleries ?? []; ?>
<style>
/* Minimal, theme-independent styling so this module looks reasonable on
   any frontend theme out of the box. A theme can override these classes
   with its own design — this is only a fallback baseline. */
.hotel-gallery-block { margin-bottom: 2.5rem; }
.hotel-gallery-block h2 { margin-bottom: .25rem; }
.hotel-gallery-block p { color: var(--site-text-muted); margin-top: 0; }
.hotel-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .75rem; }
.hotel-gallery img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 10px; display: block; }
</style>
<section class="section">
  <div class="site-container">
    <h1 class="section-title">Галерея</h1>

    <?php if (empty($galleries)): ?>
    <div class="empty-state">Галереї ще не додані.</div>
    <?php else: ?>
    <?php foreach ($galleries as $gallery): ?>
    <div class="hotel-gallery-block">
      <h2><?= e($gallery['title']) ?></h2>
      <?php if (!empty($gallery['description'])): ?><p><?= e($gallery['description']) ?></p><?php endif; ?>
      <?php if (!empty($gallery['images'])): ?>
      <div class="hotel-gallery">
        <?php foreach ($gallery['images'] as $img): ?>
        <img src="/public/userfiles/gallery/<?= rawurlencode($img['title'] ?? '') ?>" alt="<?= e($img['alt'] ?? $gallery['title'] ?? '') ?>" loading="lazy">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
