<?php $page = $page ?? []; ?>
<section class="section">
  <div class="site-container">
    <article class="card page-content">
      <h1><?= e($page['title'] ?? '') ?></h1>
      <?= $page['content'] ?? '' ?>
    </article>
  </div>
</section>
