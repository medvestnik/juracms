-- Add thank-you page and form redirect setting
INSERT IGNORE INTO jura_pages (title, slug, content, status, template, meta_title, meta_description, sort_order, created_at, updated_at)
VALUES (
  'Дякуємо за звернення',
  'thankyou',
  '<div style="text-align:center;padding:60px 20px"><div style="font-size:56px;margin-bottom:16px">&#10003;</div><h1 style="font-size:2rem;color:#1b2a4a;margin-bottom:16px">Дякуємо за звернення!</h1><p style="font-size:1.1rem;color:#4b5563;max-width:480px;margin:0 auto 32px">Ваше повідомлення отримано. Ми зв&#39;яжемося з вами найближчим часом.</p><a href="/" style="display:inline-block;background:#2563eb;color:#fff;font-weight:700;padding:13px 32px;border-radius:8px;text-decoration:none;font-size:1rem">На головну</a></div>',
  'published',
  '',
  'Дякуємо за звернення',
  'Ваше повідомлення отримано. Ми зв''яжемося з вами найближчим часом.',
  99,
  NOW(),
  NOW()
);

INSERT IGNORE INTO jura_routes (path, entity_type, entity_id, status)
SELECT '/thankyou', 'page', id, 'active'
FROM jura_pages WHERE slug = 'thankyou' LIMIT 1;

INSERT IGNORE INTO jura_settings (setting_key, setting_value, setting_type, group_name)
VALUES ('thankyou_page', '/thankyou', 'string', 'forms');
