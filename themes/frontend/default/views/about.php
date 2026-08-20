<?php
$page = $page ?? [];
$settings = $settings ?? [];
$siteName = $settings['site_name'] ?? '';
?>
<section class="hero">
  <div class="site-container">
    <span class="hero__eyebrow">Про нас</span>
    <h1><?= e($page['title'] ?? 'Про нас') ?></h1>
    <?php if (!empty($page['excerpt'])): ?>
    <p><?= e($page['excerpt']) ?></p>
    <?php elseif ($siteName !== ''): ?>
    <p>Дізнайтеся більше про <?= e($siteName) ?> — хто ми і чим займаємося.</p>
    <?php endif; ?>
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
    <div class="cta-banner">
      <h2>Маєте запитання?</h2>
      <p>Напишіть нам або зателефонуйте — відповімо якнайшвидше.</p>
      <a class="btn btn-on-dark" href="/contacts">Зв'язатися з нами</a>
    </div>
  </div>
</section>
