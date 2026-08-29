<style>
/* Minimal, theme-independent styling so this module looks reasonable on
   any frontend theme out of the box. Wrapped in :where() so every rule
   here has ZERO specificity -- a theme's own CSS for these same classes
   (even a single plain class selector) always wins, regardless of
   stylesheet load order. Without this, a theme that already styles
   .project-card/.portfolio-card itself could get fought (and lose) to
   this fallback simply because this <style> block, sitting inside the
   page body, comes after the theme's own <link> in source order --
   equal-specificity rules resolve by source order, not intent. */
:where(.catalog-hero){padding:3rem 0 2rem;text-align:center}
:where(.catalog-hero .section-kicker){text-transform:uppercase;letter-spacing:.08em;font-size:.8rem;color:#64748b;margin-bottom:.5rem}
:where(.catalog-hero h1){font-size:2rem;margin:0 0 .75rem}
:where(.catalog-hero p){color:#475569;max-width:640px;margin:0 auto}
:where(.catalog-section){padding:1rem 0 3rem}
:where(.project-grid,.portfolio-grid){display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem}
:where(.project-card,.portfolio-card){display:block;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .15s}
:where(.project-card:hover,.portfolio-card:hover){box-shadow:0 8px 24px rgba(0,0,0,.08)}
:where(.project-card__top,.portfolio-card__top){display:flex;justify-content:space-between;font-size:.75rem;color:#64748b;margin-bottom:.75rem}
:where(.project-card h3,.portfolio-card h3){margin:.25rem 0 .5rem;font-size:1.15rem}
:where(.project-card p,.portfolio-card p){color:#475569;font-size:.92rem;margin:0 0 .75rem}
:where(.project-card a){color:#4f46e5;text-decoration:none;font-weight:600}
:where(.portfolio-card__link){color:#4f46e5;font-weight:600;font-size:.9rem}
:where(.project-card__image,.portfolio-card__image){margin:-1.5rem -1.5rem .75rem;border-radius:12px 12px 0 0;overflow:hidden;aspect-ratio:16/10}
:where(.project-card__image img,.portfolio-card__image img){width:100%;height:100%;object-fit:cover;display:block}
</style>
<section class="catalog-hero"><div class="site-container"><div class="section-kicker"><?= $kind==='project'?'Власні продукти':'Обрані роботи' ?></div><h1><?= $kind==='project'?'Проєкти, які <em>вирішують задачі</em>':'Сайти, які <em>працюють для бізнесу</em>' ?></h1><p><?= $kind==='project'?'Інструменти та продукти, розроблені власною командою.':'Проєкти з різних галузей, де дизайн, структура і технології працюють на результат.' ?></p></div></section>
<section class="section catalog-section <?= $kind==='project'?'section--projects':'section--portfolio' ?>"><div class="site-container"><div class="<?= $kind==='project'?'project-grid':'portfolio-grid' ?>">
<?php foreach($items as $i=>$item): $img = !empty($item['featured_image']) ? '/public/userfiles/portfolio/' . $item['featured_image'] : null; if($kind==='project'): ?><article class="project-card project-card--<?= e($item['color']) ?> reveal"><?php if ($img): ?><div class="project-card__image"><img src="<?= e($img) ?>" alt="<?= e($item['title']) ?>" loading="lazy"></div><?php endif; ?><div class="project-card__top"><span class="project-tag"><?= e($item['category']) ?></span><span class="project-status"><?= e($item['status_label']) ?></span></div><div class="project-card__body"><span class="project-card__num"><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p><a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"><?= e($item['link_label']) ?> <span>↗</span></a></div></article><?php else: ?><a class="portfolio-card portfolio-card--<?= e($item['color']) ?> reveal" href="<?= e($item['url']) ?>" target="_blank" rel="noopener"><?php if ($img): ?><div class="portfolio-card__image"><img src="<?= e($img) ?>" alt="<?= e($item['title']) ?>" loading="lazy"></div><?php endif; ?><div class="portfolio-card__top"><span><?= e($item['category']) ?></span><span><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span></div><div><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p></div><span class="portfolio-card__link"><?= e($item['link_label']) ?> ↗</span></a><?php endif; endforeach; ?>
</div></div></section>
