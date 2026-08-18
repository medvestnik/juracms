<?php $promotion = $promotion ?? []; ?>
<section class="section">
  <div class="site-container" style="max-width:780px">
    <a class="back-link" href="/promotions">&larr; Усі акції</a>
    <article class="card page-content">
      <h1><?= e($promotion['title'] ?? '') ?></h1>
      <?= $promotion['content'] ?? '' ?>
    </article>
  </div>
</section>
