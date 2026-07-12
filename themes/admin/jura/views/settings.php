<?php $s = $settings ?? []; ?>
<?php if (!empty($success)): ?>
  <div class="jura-alert" style="margin-bottom:1rem"><?= e($success) ?></div>
<?php endif; ?>
<form method="post">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Сайт та меню</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Назва сайту</label>
        <input class="jura-input" name="settings[site_name]" value="<?= e($s['site_name'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">URL сайту</label>
        <input class="jura-input" name="settings[site_url]" value="<?= e($s['site_url'] ?? '') ?>" placeholder="https://example.com">
      </div>
      <div>
        <label class="jura-label">Меню хедера (код)</label>
        <input class="jura-input" name="settings[menu_header]" value="<?= e($s['menu_header'] ?? 'main') ?>" placeholder="main">
        <small style="color:#888">Код меню зі сторінки <a href="/admin/menus">Menus</a></small>
      </div>
      <div>
        <label class="jura-label">Меню футера (код)</label>
        <input class="jura-input" name="settings[menu_footer]" value="<?= e($s['menu_footer'] ?? 'main') ?>" placeholder="main">
      </div>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Контакти</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Телефон 1</label>
        <input class="jura-input" name="settings[contact_phone]" value="<?= e($s['contact_phone'] ?? '') ?>" placeholder="+380 63 000 00 00">
      </div>
      <div>
        <label class="jura-label">Телефон 2</label>
        <input class="jura-input" name="settings[contact_phone2]" value="<?= e($s['contact_phone2'] ?? '') ?>" placeholder="+380 67 000 00 00">
      </div>
      <div>
        <label class="jura-label">Email</label>
        <input class="jura-input" name="settings[contact_email]" value="<?= e($s['contact_email'] ?? '') ?>" placeholder="info@example.com">
      </div>
      <div>
        <label class="jura-label">Адреса</label>
        <input class="jura-input" name="settings[contact_address]" value="<?= e($s['contact_address'] ?? '') ?>" placeholder="вул. Хрещатик 1, Київ">
      </div>
      <div>
        <label class="jura-label">Facebook</label>
        <input class="jura-input" name="settings[social_facebook]" value="<?= e($s['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/...">
      </div>
      <div>
        <label class="jura-label">Instagram</label>
        <input class="jura-input" name="settings[social_instagram]" value="<?= e($s['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/...">
      </div>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Інтеграції</h2>
    <div style="margin-bottom:1rem">
      <label class="jura-label">Google Maps embed-код (iframe)</label>
      <textarea class="jura-input" name="settings[google_maps_embed]" rows="4" placeholder='&lt;iframe src="https://www.google.com/maps/embed?..." ...&gt;&lt;/iframe&gt;'><?= e($s['google_maps_embed'] ?? '') ?></textarea>
      <small style="color:#888">Скопіюйте iframe-код з Google Maps (Поділитися → Вставити карту)</small>
    </div>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">GTM ID</label>
        <input class="jura-input" name="settings[gtm_id]" value="<?= e($s['gtm_id'] ?? '') ?>" placeholder="GTM-XXXXXXX">
      </div>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Аналітика та реклама</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Google Analytics 4 — Measurement ID</label>
        <input class="jura-input" name="settings[ga4_id]" value="<?= e($s['ga4_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
        <small style="color:#888">Ідентифікатор потоку даних GA4. Починається з G-</small>
      </div>
      <div>
        <label class="jura-label">Google Ads — Conversion ID</label>
        <input class="jura-input" name="settings[google_ads_id]" value="<?= e($s['google_ads_id'] ?? '') ?>" placeholder="AW-XXXXXXXXX">
        <small style="color:#888">ID конверсії Google Ads. Починається з AW-</small>
      </div>
      <div>
        <label class="jura-label">Facebook Pixel ID</label>
        <input class="jura-input" name="settings[fb_pixel_id]" value="<?= e($s['fb_pixel_id'] ?? '') ?>" placeholder="123456789012345">
        <small style="color:#888">Числовий ID пікселя Facebook/Meta</small>
      </div>
      <div>
        <label class="jura-label">Facebook Conversions API — Access Token</label>
        <input class="jura-input" name="settings[fb_access_token]" value="<?= e($s['fb_access_token'] ?? '') ?>" placeholder="EAAxxxxxxxxxx...">
        <small style="color:#888">Токен для серверних подій Meta CAPI (необов'язково)</small>
      </div>
    </div>
    <p style="font-size:.8rem;color:#94a3b8;margin-top:.75rem">Через GTM можна керувати GA4, Google Ads та Pixel без зміни коду — достатньо вказати GTM ID вище.</p>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Сповіщення про заявки</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Email для заявок</label>
        <input class="jura-input" name="settings[notification_email_to]" value="<?= e($s['notification_email_to'] ?? '') ?>" placeholder="manager@example.com">
      </div>
      <div>
        <label class="jura-label">Telegram bot token</label>
        <input class="jura-input" name="settings[telegram_bot_token]" value="<?= e($s['telegram_bot_token'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Telegram chat ID</label>
        <input class="jura-input" name="settings[telegram_chat_id]" value="<?= e($s['telegram_chat_id'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Сторінка «Дякуємо» після відправки форми</label>
        <input class="jura-input" name="settings[thankyou_page]" value="<?= e($s['thankyou_page'] ?? '/thankyou') ?>" placeholder="/thankyou">
        <small style="color:#94a3b8">URL сторінки, куди потрапляє користувач після відправки будь-якої форми на сайті.</small>
      </div>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Публікації (блог)</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Кількість публікацій на сторінці</label>
        <input class="jura-input" type="number" min="1" max="100" name="settings[blog_per_page]" value="<?= e($s['blog_per_page'] ?? '12') ?>" style="max-width:120px">
        <small style="color:#94a3b8;display:block;margin-top:.3rem">За замовчуванням 12. Застосовується на сторінці /blog.</small>
      </div>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Зовнішній вигляд адмінки</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Кольорова схема Jura UI</label>
        <select class="jura-input" name="settings[admin_jura_theme]">
          <?php foreach (['indigo'=>'Indigo (за замовч.)', 'ocean'=>'Ocean (блакитний)', 'emerald'=>'Emerald (зелений)', 'rose'=>'Rose (рожевий)', 'violet'=>'Violet (фіолетовий)', 'amber'=>'Amber (жовтий)', 'slate'=>'Slate (сірий)'] as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= ($s['admin_jura_theme'] ?? 'indigo') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <small style="color:#888">Зміна набирає чинності після збереження та перезавантаження сторінки</small>
      </div>
      <div>
        <label class="jura-label">Темна тема</label>
        <label style="display:flex;align-items:center;gap:.5rem;margin-top:.4rem">
          <input type="checkbox" name="settings[admin_dark_mode]" value="1" <?= ($s['admin_dark_mode'] ?? '') === '1' ? 'checked' : '' ?>>
          Увімкнути темний режим
        </label>
      </div>
    </div>
  </section>

  <button class="jura-btn jura-btn-primary" type="submit">Зберегти налаштування</button>
</form>

<hr style="margin:2rem 0">
<section class="jura-card">
  <h2 style="margin-top:0">Оновлення бібліотек UI</h2>
  <p style="color:#64748b;font-size:.875rem">Завантажує останні версії Jura UI та Simple JS Editor з GitHub і замінює файли в проекті.</p>
  <div style="display:flex;gap:1rem;flex-wrap:wrap">
    <form method="post" action="/admin/update-library">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="lib" value="juraui">
      <button class="jura-btn jura-btn-secondary" type="submit"
        onclick="return confirm('Оновити Jura UI з GitHub?')">
        ↻ Оновити Jura UI
      </button>
    </form>
    <form method="post" action="/admin/update-library">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="lib" value="simple-js-editor">
      <button class="jura-btn jura-btn-secondary" type="submit"
        onclick="return confirm('Оновити Simple JS Editor з GitHub?')">
        ↻ Оновити Simple JS Editor
      </button>
    </form>
  </div>
  <?php if (!empty($lib_update_result)): ?>
  <div class="jura-alert" style="margin-top:1rem;<?= str_contains($lib_update_result, 'Помилка') ? 'background:#fff1f2;border-color:#fecdd3;color:#9f1239' : '' ?>"><?= e($lib_update_result) ?></div>
  <?php endif; ?>
</section>
