<?php
$page = $page ?? [];
$settings = $settings ?? [];
$mapEmbed = trim((string) ($settings['google_maps_embed'] ?? ''));
?>
<section class="section">
  <div class="site-container">
    <h1 class="section-title"><?= e($page['title'] ?? 'Контакти') ?></h1>

    <div class="contact-grid">
      <div class="card">
        <?php if (!empty($page['content'])): ?>
        <div class="page-content" style="margin-bottom:1.5rem"><?= $page['content'] ?></div>
        <?php endif; ?>

        <ul class="contact-list">
          <?php if (!empty($settings['contact_phone'])): ?>
          <li><span class="label">Телефон</span> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $settings['contact_phone'])) ?>"><?= e($settings['contact_phone']) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($settings['contact_phone2'])): ?>
          <li><span class="label">Телефон 2</span> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $settings['contact_phone2'])) ?>"><?= e($settings['contact_phone2']) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($settings['contact_email'])): ?>
          <li><span class="label">Email</span> <a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($settings['contact_address'])): ?>
          <li><span class="label">Адреса</span> <span><?= e($settings['contact_address']) ?></span></li>
          <?php endif; ?>
        </ul>

        <form method="post" action="/forms/contact" style="margin-top:1.75rem;display:grid;gap:.75rem;max-width:420px">
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input class="jura-input" style="margin:0" type="text" name="name" placeholder="Ваше ім'я" required>
          <input class="jura-input" style="margin:0" type="text" name="phone" placeholder="Телефон">
          <input class="jura-input" style="margin:0" type="email" name="email" placeholder="Email">
          <textarea class="jura-input" style="margin:0" name="message" rows="4" placeholder="Повідомлення"></textarea>
          <button class="btn btn-primary" type="submit">Надіслати</button>
        </form>
      </div>

      <?php if ($mapEmbed !== ''): ?>
      <div class="contact-map card"><?= $mapEmbed ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
