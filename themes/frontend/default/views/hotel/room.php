<?php
$room = $room ?? [];
$similarRooms = $similar_rooms ?? [];
$roomImages = $room['room_images'] ?? [];
$amenities = $room['amenity_list'] ?? [];
$rates = $room['rates'] ?? [];
?>
<style>
/* Minimal, theme-independent styling so this module looks reasonable on
   any frontend theme out of the box. A theme can override these classes
   with its own design — this is only a fallback baseline. */
.hotel-price { font-weight: 700; color: var(--site-primary); font-size: 1.05rem; }
.hotel-meta { color: var(--site-text-muted); font-size: .92rem; }
.hotel-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .75rem; margin: 1.5rem 0; }
.hotel-gallery img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 10px; display: block; }
.hotel-amenities { list-style: none; display: flex; flex-wrap: wrap; gap: .5rem; margin: 0; padding: 0; }
.hotel-amenities li { display: inline-flex; align-items: center; gap: .4rem; background: var(--site-surface-muted); border: 1px solid var(--site-border); border-radius: 999px; padding: .4rem .85rem; font-size: .88rem; }
.hotel-rates { width: 100%; border-collapse: collapse; margin: 0; }
.hotel-rates th, .hotel-rates td { text-align: left; padding: .55rem .75rem; border-bottom: 1px solid var(--site-border); font-size: .92rem; }
.hotel-rates th { color: var(--site-text-muted); font-weight: 600; }
.hotel-lead-form { display: grid; gap: .75rem; max-width: 480px; }
.hotel-lead-form input, .hotel-lead-form textarea {
  padding: .7rem .85rem; border-radius: 10px; border: 1px solid var(--site-border);
  background: var(--site-surface); color: var(--site-text); font: inherit;
}
</style>

<section class="section">
  <div class="site-container" style="max-width:900px">
    <a class="back-link" href="/rooms">&larr; Усі номери</a>
    <article class="card page-content">
      <h1><?= e($room['title'] ?? '') ?></h1>
      <p class="hotel-price">
        від <?= e($room['price_from'] ?? '0') ?> <?= e($room['currency'] ?? '') ?>
        <?php if (!empty($room['area']) || !empty($room['capacity'])): ?>
        <span class="hotel-meta"> · <?= e($room['area'] ?? '') ?><?= (!empty($room['area']) && !empty($room['capacity'])) ? ', ' : '' ?><?= e($room['capacity'] ?? '') ?></span>
        <?php endif; ?>
      </p>

      <?php if (!empty($roomImages)): ?>
      <div class="hotel-gallery">
        <?php foreach ($roomImages as $img): ?>
        <img src="/public/userfiles/rooms/<?= rawurlencode($img['title']) ?>" alt="<?= e($img['alt'] ?? $room['title'] ?? '') ?>" loading="lazy">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?= $room['description'] ?? '' ?>

      <?php if (!empty($amenities)): ?>
      <h2>Зручності</h2>
      <ul class="hotel-amenities">
        <?php foreach ($amenities as $a): ?>
        <li><?= e($a['icon'] ?? '') ?> <?= e($a['title'] ?? '') ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <?php if (!empty($rates)): ?>
      <h2>Тарифи</h2>
      <table class="hotel-rates">
        <thead><tr><th>Тариф</th><th>Гостей</th><th>Ціна/доба</th></tr></thead>
        <tbody>
        <?php foreach ($rates as $rt): ?>
        <tr>
          <td><?= e($rt['tariff']) ?></td>
          <td><?= (int) $rt['guests'] ?></td>
          <td><?= e($rt['price']) ?> <?= e($room['currency'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <h2>Заявка на бронювання</h2>
      <form class="hotel-lead-form" method="post" action="/forms/booking">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="room" value="<?= e($room['title'] ?? '') ?>">
        <input name="name" placeholder="Ім'я" required>
        <input name="phone" placeholder="Телефон" required>
        <input name="email" type="email" placeholder="Email">
        <textarea name="message" placeholder="Коментар" rows="3"></textarea>
        <button class="btn btn-primary" type="submit">Надіслати</button>
      </form>
    </article>

    <?php if (!empty($similarRooms)): ?>
    <section class="section" style="padding-bottom:0">
      <h2 class="section-title">Схожі номери</h2>
      <div class="post-grid">
        <?php foreach ($similarRooms as $sr): ?>
        <article class="post-card">
          <?php if (!empty($sr['_feat_img'])): ?>
          <div class="post-card__image"><img src="/public/userfiles/rooms/<?= e($sr['_feat_img']) ?>" alt="<?= e($sr['title']) ?>" loading="lazy"></div>
          <?php endif; ?>
          <div class="post-card__body">
            <h3 class="post-card__title"><a href="/rooms/<?= e($sr['slug']) ?>"><?= e($sr['title']) ?></a></h3>
            <span class="hotel-price">від <?= e($sr['price_from']) ?> <?= e($sr['currency']) ?></span>
            <a class="post-card__more" href="/rooms/<?= e($sr['slug']) ?>">Докладніше →</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</section>
