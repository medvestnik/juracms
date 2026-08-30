<?php

declare(strict_types=1);

use App\Core\BlockRegistry;

// ── hero ─────────────────────────────────────────────────────────────────
// Same markup/classes as the old hardcoded home.php hero, so migrating an
// existing home page to a "hero" block looks identical out of the box.
BlockRegistry::register('hero', [
    'label' => 'Hero (заголовок + кнопки)',
    'admin_form' => function (array $s): string {
        ob_start(); ?>
        <div class="jura-grid jura-grid-2" style="gap:1rem">
          <div><label class="jura-label">Наднапис (eyebrow)</label><input class="jura-input" name="eyebrow" value="<?= e($s['eyebrow'] ?? '') ?>"></div>
          <div><label class="jura-label">Заголовок</label><input class="jura-input" name="title" value="<?= e($s['title'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:1rem"><label class="jura-label">Підзаголовок</label><textarea class="jura-input" name="subtitle" rows="2"><?= e($s['subtitle'] ?? '') ?></textarea></div>
        <div class="jura-grid jura-grid-2" style="gap:1rem;margin-top:1rem">
          <div><label class="jura-label">Кнопка 1: текст</label><input class="jura-input" name="primary_label" value="<?= e($s['primary_label'] ?? '') ?>"></div>
          <div><label class="jura-label">Кнопка 1: посилання</label><input class="jura-input" name="primary_url" value="<?= e($s['primary_url'] ?? '') ?>"></div>
          <div><label class="jura-label">Кнопка 2: текст</label><input class="jura-input" name="secondary_label" value="<?= e($s['secondary_label'] ?? '') ?>"></div>
          <div><label class="jura-label">Кнопка 2: посилання</label><input class="jura-input" name="secondary_url" value="<?= e($s['secondary_url'] ?? '') ?>"></div>
        </div>
        <?php
        return (string) ob_get_clean();
    },
    'save_settings' => fn(array $p): array => [
        'eyebrow' => trim((string) ($p['eyebrow'] ?? '')),
        'title' => trim((string) ($p['title'] ?? '')),
        'subtitle' => trim((string) ($p['subtitle'] ?? '')),
        'primary_label' => trim((string) ($p['primary_label'] ?? '')),
        'primary_url' => trim((string) ($p['primary_url'] ?? '')),
        'secondary_label' => trim((string) ($p['secondary_label'] ?? '')),
        'secondary_url' => trim((string) ($p['secondary_url'] ?? '')),
    ],
    'render' => function (array $s): string {
        ob_start(); ?>
        <section class="hero">
          <div class="site-container">
            <?php if (!empty($s['eyebrow'])): ?><span class="hero__eyebrow"><?= e($s['eyebrow']) ?></span><?php endif; ?>
            <?php if (!empty($s['title'])): ?><h1><?= e($s['title']) ?></h1><?php endif; ?>
            <?php if (!empty($s['subtitle'])): ?><p><?= e($s['subtitle']) ?></p><?php endif; ?>
            <?php if (!empty($s['primary_label']) || !empty($s['secondary_label'])): ?>
            <div class="hero__actions">
              <?php if (!empty($s['primary_label'])): ?><a class="btn btn-primary" href="<?= e($s['primary_url'] ?: '#') ?>"><?= e($s['primary_label']) ?></a><?php endif; ?>
              <?php if (!empty($s['secondary_label'])): ?><a class="btn btn-secondary" href="<?= e($s['secondary_url'] ?: '#') ?>"><?= e($s['secondary_label']) ?></a><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </section>
        <?php
        return (string) ob_get_clean();
    },
]);

// ── richtext ─────────────────────────────────────────────────────────────
BlockRegistry::register('richtext', [
    'label' => 'Текст / HTML',
    'admin_form' => function (array $s): string {
        ob_start(); ?>
        <label class="jura-label">Вміст</label>
        <textarea class="jura-input" name="content" data-editor="simple-js-editor" rows="10"><?= e($s['content'] ?? '') ?></textarea>
        <?php
        return (string) ob_get_clean();
    },
    'save_settings' => fn(array $p): array => ['content' => (string) ($p['content'] ?? '')],
    'render' => function (array $s): string {
        if (trim((string) ($s['content'] ?? '')) === '') {
            return '';
        }
        return '<section class="section"><div class="site-container card page-content">' . $s['content'] . '</div></section>';
    },
]);

// ── cta ──────────────────────────────────────────────────────────────────
BlockRegistry::register('cta', [
    'label' => 'CTA-банер',
    'admin_form' => function (array $s): string {
        ob_start(); ?>
        <div class="jura-grid jura-grid-2" style="gap:1rem">
          <div><label class="jura-label">Заголовок</label><input class="jura-input" name="title" value="<?= e($s['title'] ?? '') ?>"></div>
          <div><label class="jura-label">Кнопка: текст</label><input class="jura-input" name="button_label" value="<?= e($s['button_label'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:1rem"><label class="jura-label">Текст</label><textarea class="jura-input" name="text" rows="2"><?= e($s['text'] ?? '') ?></textarea></div>
        <div style="margin-top:1rem"><label class="jura-label">Кнопка: посилання</label><input class="jura-input" name="button_url" value="<?= e($s['button_url'] ?? '') ?>"></div>
        <?php
        return (string) ob_get_clean();
    },
    'save_settings' => fn(array $p): array => [
        'title' => trim((string) ($p['title'] ?? '')),
        'text' => trim((string) ($p['text'] ?? '')),
        'button_label' => trim((string) ($p['button_label'] ?? '')),
        'button_url' => trim((string) ($p['button_url'] ?? '')),
    ],
    'render' => function (array $s): string {
        if (empty($s['title']) && empty($s['text'])) {
            return '';
        }
        ob_start(); ?>
        <section class="section">
          <div class="site-container">
            <div class="cta-banner">
              <?php if (!empty($s['title'])): ?><h2><?= e($s['title']) ?></h2><?php endif; ?>
              <?php if (!empty($s['text'])): ?><p><?= e($s['text']) ?></p><?php endif; ?>
              <?php if (!empty($s['button_label'])): ?><a class="btn btn-on-dark" href="<?= e($s['button_url'] ?: '#') ?>"><?= e($s['button_label']) ?></a><?php endif; ?>
            </div>
          </div>
        </section>
        <?php
        return (string) ob_get_clean();
    },
]);

// ── feature_grid ─────────────────────────────────────────────────────────
// Fixed 4 cards (matches the old hardcoded home.php grid) rather than a
// dynamic repeater -- simplest possible admin UI for a v1.
BlockRegistry::register('feature_grid', [
    'label' => 'Сітка переваг (4 картки)',
    'admin_form' => function (array $s): string {
        $items = $s['items'] ?? [];
        ob_start(); ?>
        <div class="jura-grid jura-grid-2" style="gap:1rem">
          <div><label class="jura-label">Заголовок секції</label><input class="jura-input" name="title" value="<?= e($s['title'] ?? '') ?>"></div>
          <div><label class="jura-label">Підзаголовок секції</label><input class="jura-input" name="subtitle" value="<?= e($s['subtitle'] ?? '') ?>"></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-top:1rem">
          <?php for ($i = 0; $i < 4; $i++): $it = $items[$i] ?? []; ?>
          <fieldset style="border:1px solid var(--jura-border,#e2e8f0);border-radius:8px;padding:.75rem">
            <legend style="font-size:.8rem;color:#94a3b8">Картка <?= $i + 1 ?></legend>
            <input class="jura-input" style="margin-bottom:.5rem" name="icon[]" placeholder="Емодзі/іконка" value="<?= e($it['icon'] ?? '') ?>">
            <input class="jura-input" style="margin-bottom:.5rem" name="title[]" placeholder="Заголовок" value="<?= e($it['title'] ?? '') ?>">
            <textarea class="jura-input" name="text[]" placeholder="Текст" rows="2"><?= e($it['text'] ?? '') ?></textarea>
          </fieldset>
          <?php endfor; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    },
    'save_settings' => function (array $p): array {
        $icons = (array) ($p['icon'] ?? []);
        $titles = (array) ($p['title'] ?? []);
        $texts = (array) ($p['text'] ?? []);
        $items = [];
        for ($i = 0; $i < 4; $i++) {
            $items[] = [
                'icon' => trim((string) ($icons[$i] ?? '')),
                'title' => trim((string) ($titles[$i] ?? '')),
                'text' => trim((string) ($texts[$i] ?? '')),
            ];
        }
        return ['title' => trim((string) ($p['section_title'] ?? '')), 'subtitle' => trim((string) ($p['subtitle'] ?? '')), 'items' => $items];
    },
    'render' => function (array $s): string {
        $items = array_filter($s['items'] ?? [], static fn(array $it): bool => $it['title'] !== '' || $it['text'] !== '');
        if (empty($items) && empty($s['title'])) {
            return '';
        }
        ob_start(); ?>
        <section class="section">
          <div class="site-container">
            <?php if (!empty($s['title'])): ?><h2 class="section-title"><?= e($s['title']) ?></h2><?php endif; ?>
            <?php if (!empty($s['subtitle'])): ?><p class="section-subtitle"><?= e($s['subtitle']) ?></p><?php endif; ?>
            <?php if (!empty($items)): ?>
            <div class="feature-grid">
              <?php foreach ($items as $it): ?>
              <div class="feature-card">
                <?php if (!empty($it['icon'])): ?><div class="feature-card__icon"><?= e($it['icon']) ?></div><?php endif; ?>
                <?php if (!empty($it['title'])): ?><h3><?= e($it['title']) ?></h3><?php endif; ?>
                <?php if (!empty($it['text'])): ?><p><?= e($it['text']) ?></p><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </section>
        <?php
        return (string) ob_get_clean();
    },
]);

// ── latest_posts ─────────────────────────────────────────────────────────
BlockRegistry::register('latest_posts', [
    'label' => 'Останні публікації',
    'admin_form' => function (array $s): string {
        ob_start(); ?>
        <div class="jura-grid jura-grid-2" style="gap:1rem">
          <div><label class="jura-label">Заголовок секції</label><input class="jura-input" name="title" value="<?= e($s['title'] ?? 'Останні публікації') ?>"></div>
          <div><label class="jura-label">Кількість</label><input class="jura-input" type="number" name="limit" min="1" max="12" value="<?= (int) ($s['limit'] ?? 3) ?>"></div>
        </div>
        <?php
        return (string) ob_get_clean();
    },
    'save_settings' => fn(array $p): array => [
        'title' => trim((string) ($p['title'] ?? '')),
        'limit' => max(1, min(12, (int) ($p['limit'] ?? 3))),
    ],
    'render' => function (array $s, PDO $pdo): string {
        $limit = max(1, min(12, (int) ($s['limit'] ?? 3)));
        try {
            $posts = $pdo->query('SELECT * FROM ' . jura_table('posts') . " WHERE status='published' ORDER BY published_at DESC, id DESC LIMIT {$limit}")->fetchAll();
        } catch (\Throwable) {
            $posts = [];
        }
        if (empty($posts)) {
            return '';
        }
        ob_start(); ?>
        <section class="section">
          <div class="site-container">
            <?php if (!empty($s['title'])): ?><h2 class="section-title"><?= e($s['title']) ?></h2><?php endif; ?>
            <div class="post-grid">
              <?php foreach ($posts as $post): ?>
              <article class="post-card">
                <?php if (!empty($post['featured_image'])): ?>
                <div class="post-card__image"><img src="/public/userfiles/posts/<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></div>
                <?php endif; ?>
                <div class="post-card__body">
                  <?php if (!empty($post['published_at'])): ?><span class="post-card__date"><?= e(substr((string) $post['published_at'], 0, 10)) ?></span><?php endif; ?>
                  <h3 class="post-card__title"><a href="/blog/<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                  <?php if (!empty($post['excerpt'])): ?><p class="post-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
                  <a class="post-card__more" href="/blog/<?= e($post['slug']) ?>">Читати далі →</a>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
        <?php
        return (string) ob_get_clean();
    },
]);

// ── image ────────────────────────────────────────────────────────────────
// Upload itself goes through a dedicated /upload-image sub-route (same
// save-first pattern as Posts/Portfolio featured images) since a block's
// regular settings form isn't multipart; admin_form only shows the current
// image + alt/caption fields (alt/caption ARE saved via the normal form).
BlockRegistry::register('image', [
    'label' => 'Зображення',
    'admin_form' => function (array $s): string {
        $img = !empty($s['image']) ? '/public/userfiles/blocks/' . $s['image'] : null;
        ob_start(); ?>
        <?php if ($img): ?>
        <img src="<?= e($img) ?>" alt="" style="max-width:280px;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:.75rem;display:block">
        <?php else: ?>
        <p style="color:#94a3b8;font-size:.85rem;margin:0 0 .75rem">Зображення ще не завантажено.</p>
        <?php endif; ?>
        <div class="jura-grid jura-grid-2" style="gap:1rem">
          <div><label class="jura-label">Alt-текст</label><input class="jura-input" name="alt" value="<?= e($s['alt'] ?? '') ?>"></div>
          <div><label class="jura-label">Підпис</label><input class="jura-input" name="caption" value="<?= e($s['caption'] ?? '') ?>"></div>
        </div>
        <?php
        return (string) ob_get_clean();
    },
    'save_settings' => fn(array $p, array $current = []): array => [
        'image' => $current['image'] ?? null,
        'alt' => trim((string) ($p['alt'] ?? '')),
        'caption' => trim((string) ($p['caption'] ?? '')),
    ],
    'render' => function (array $s): string {
        if (empty($s['image'])) {
            return '';
        }
        ob_start(); ?>
        <section class="section"><div class="site-container">
          <figure class="block-image">
            <img src="/public/userfiles/blocks/<?= e($s['image']) ?>" alt="<?= e($s['alt'] ?? '') ?>" loading="lazy">
            <?php if (!empty($s['caption'])): ?><figcaption><?= e($s['caption']) ?></figcaption><?php endif; ?>
          </figure>
        </div></section>
        <?php
        return (string) ob_get_clean();
    },
]);
