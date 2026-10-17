<?php
$page = $page ?? [];
$settings = $settings ?? [];
$mapEmbed = trim((string) ($settings['google_maps_embed'] ?? ''));
$recaptchaSiteKey = trim((string) ($settings['recaptcha_site_key'] ?? ''));
$formError = (($_GET['form_error'] ?? '') === 'contact') ? 'Підтвердьте, що ви не робот, і спробуйте ще раз.' : null;
$showPhone = ($settings['show_phone'] ?? '1') !== '0';
$contactCards = [];
if ($showPhone && !empty($settings['contact_phone'])) {
    $contactCards[] = ['📞', 'Телефон', $settings['contact_phone'], 'tel:' . preg_replace('/[^0-9+]/', '', (string) $settings['contact_phone'])];
}
if ($showPhone && !empty($settings['contact_phone2'])) {
    $contactCards[] = ['📞', 'Телефон 2', $settings['contact_phone2'], 'tel:' . preg_replace('/[^0-9+]/', '', (string) $settings['contact_phone2'])];
}
if (!empty($settings['contact_email'])) {
    $contactCards[] = ['✉️', 'Email', $settings['contact_email'], 'mailto:' . $settings['contact_email']];
}
if (!empty($settings['contact_address'])) {
    $contactCards[] = ['📍', 'Адреса', $settings['contact_address'], null];
}
?>
<section class="hero">
  <div class="site-container">
    <span class="hero__eyebrow">Контакти</span>
    <h1><?= e($page['title'] ?? 'Контакти') ?></h1>
    <p>Залишились питання? Напишіть нам або зателефонуйте — відповімо якнайшвидше.</p>
  </div>
</section>

<?php if (!empty($contactCards)): ?>
<section class="section" style="padding-top:0">
  <div class="site-container">
    <div class="feature-grid">
      <?php foreach ($contactCards as [$icon, $label, $value, $href]): ?>
      <div class="feature-card">
        <div class="feature-card__icon"><?= $icon ?></div>
        <h3><?= e($label) ?></h3>
        <?php if ($href): ?>
        <p><a href="<?= e($href) ?>"><?= e($value) ?></a></p>
        <?php else: ?>
        <p><?= e($value) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0">
  <div class="site-container contact-grid" <?= $mapEmbed === '' ? 'style="grid-template-columns:1fr;max-width:640px"' : '' ?>>
    <div class="card">
      <?php if (!empty($page['content'])): ?>
      <div class="page-content" style="margin-bottom:1.5rem"><?= $page['content'] ?></div>
      <?php endif; ?>

      <h2 style="margin-top:0">Написати нам</h2>
      <?php if ($formError): ?>
      <p style="color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.6rem .9rem;margin:0 0 .75rem"><?= e($formError) ?></p>
      <?php endif; ?>
      <form method="post" action="/forms/contact" style="display:grid;gap:.75rem;max-width:420px">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input class="jura-input" style="margin:0" type="text" name="name" placeholder="Ваше ім'я" required>
        <input class="jura-input" style="margin:0" type="text" name="phone" placeholder="Телефон">
        <input class="jura-input" style="margin:0" type="email" name="email" placeholder="Email">
        <textarea class="jura-input" style="margin:0" name="message" rows="4" placeholder="Повідомлення"></textarea>
        <?php if ($recaptchaSiteKey !== ''): ?>
        <div class="g-recaptcha" data-sitekey="<?= e($recaptchaSiteKey) ?>"></div>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">Надіслати</button>
      </form>
      <?php if ($recaptchaSiteKey !== ''): ?>
      <script src="https://www.google.com/recaptcha/api.js" async defer></script>
      <?php endif; ?>
    </div>

    <?php if ($mapEmbed !== ''): ?>
    <div class="contact-map card"><?= $mapEmbed ?></div>
    <?php endif; ?>
  </div>
</section>
