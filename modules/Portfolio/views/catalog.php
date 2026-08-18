<style>
/* Minimal, theme-independent styling so this module looks reasonable on
   any frontend theme out of the box. A theme can override these classes
   with its own design — this is only a fallback baseline. */
.catalog-hero{padding:3rem 0 2rem;text-align:center}
.catalog-hero .section-kicker{text-transform:uppercase;letter-spacing:.08em;font-size:.8rem;color:#64748b;margin-bottom:.5rem}
.catalog-hero h1{font-size:2rem;margin:0 0 .75rem}
.catalog-hero p{color:#475569;max-width:640px;margin:0 auto}
.catalog-section{padding:1rem 0 3rem}
.project-grid,.portfolio-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem}
.project-card,.portfolio-card{display:block;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .15s}
.project-card:hover,.portfolio-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.08)}
.project-card__top,.portfolio-card__top{display:flex;justify-content:space-between;font-size:.75rem;color:#64748b;margin-bottom:.75rem}
.project-card h3,.portfolio-card h3{margin:.25rem 0 .5rem;font-size:1.15rem}
.project-card p,.portfolio-card p{color:#475569;font-size:.92rem;margin:0 0 .75rem}
.project-card a{color:#4f46e5;text-decoration:none;font-weight:600}
.portfolio-card__link{color:#4f46e5;font-weight:600;font-size:.9rem}
</style>
<section class="catalog-hero"><div class="site-container"><div class="section-kicker"><?= $kind==='project'?'Собственные продукты':'Избранные работы' ?></div><h1><?= $kind==='project'?'Проекты, которые <em>решают задачи</em>':'Сайты, которые <em>работают для бизнеса</em>' ?></h1><p><?= $kind==='project'?'Экосистема инструментов для сайтов, серверов, командной работы и образовательных проектов.':'Проекты из разных отраслей, где дизайн, структура и технологии работают на результат.' ?></p></div></section>
<section class="section catalog-section <?= $kind==='project'?'section--projects':'section--portfolio' ?>"><div class="site-container"><div class="<?= $kind==='project'?'project-grid':'portfolio-grid' ?>">
<?php foreach($items as $i=>$item): if($kind==='project'): ?><article class="project-card project-card--<?= e($item['color']) ?> reveal"><div class="project-card__top"><span class="project-tag"><?= e($item['category']) ?></span><span class="project-status"><?= e($item['status_label']) ?></span></div><div class="project-card__body"><span class="project-card__num"><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p><a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"><?= e($item['link_label']) ?> <span>↗</span></a></div></article><?php else: ?><a class="portfolio-card portfolio-card--<?= e($item['color']) ?> reveal" href="<?= e($item['url']) ?>" target="_blank" rel="noopener"><div class="portfolio-card__top"><span><?= e($item['category']) ?></span><span><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span></div><div><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p></div><span class="portfolio-card__link"><?= e($item['link_label']) ?> ↗</span></a><?php endif; endforeach; ?>
</div></div></section>
