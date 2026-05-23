<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Jura CMS Installer</title><link rel="stylesheet" href="/install/assets/installer.css"></head>
<body><div class="wrap"><div class="card"><h1>Jura CMS уже установлена</h1>
<ul>
<li><b>site_name:</b> <?= e($siteName) ?></li>
<li><b>version:</b> <?= e((string)($config['app']['version'] ?? $version)) ?></li>
<?php if ($adminEmail): ?><li><b>admin_email:</b> <?= e((string)$adminEmail) ?></li><?php endif; ?>
</ul>
<?php if ($usersWarning): ?><div class="err"><?= e($usersWarning) ?></div><?php endif; ?>
<p><a href="/">Перейти на сайт</a> · <a href="/admin/login">Открыть админку</a></p>
<?php if ((bool)($config['app']['allow_install_reset'] ?? false)): ?>
<form method="post" onsubmit="return confirm('Сбросить установку?');">
<input type="hidden" name="install_action" value="reset-installation">
<input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
<button>Reset installation</button>
</form>
<?php endif; ?>
</div></div></body></html>
