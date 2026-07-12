<h1>Dashboard</h1><div class="jura-grid jura-grid-4">
<section class="jura-card">Сторінки: <?=e((string)($stats['pages']??0))?></section>
<section class="jura-card">Публікації: <?=e((string)($stats['posts']??0))?></section>
<section class="jura-card">Медіа: <?=e((string)($stats['media_files']??0))?></section>
<section class="jura-card">Користувачі: <?=e((string)($stats['users']??0))?></section>
<section class="jura-card">Редіректи: <?=e((string)($stats['redirects']??0))?></section>
<section class="jura-card">Пункти меню: <?=e((string)($stats['menu_items']??0))?></section>
<section class="jura-card">Заявки: <?=e((string)($stats['leads']??0))?></section>
</div>
