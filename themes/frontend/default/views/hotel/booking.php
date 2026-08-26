<?php $page = $page ?? null; ?>
<section class="section">
  <div class="site-container">
    <h1 class="section-title"><?= e($page['title'] ?? 'Бронювання') ?></h1>

    <?php if ($page && !empty($page['content'])): ?>
    <div class="page-content"><?= $page['content'] ?></div>
    <?php else: ?>
    <div class="empty-state">{{ booking-page-form }}</div>
    <?php endif; ?>
  </div>
</section>
