<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка Jura CMS</title>
    <link rel="stylesheet" href="/public/assets/cms/installer.css">
</head>
<body>
<div class="installer-shell">
    <h1>Установка Jura CMS</h1>
    <p>Мастер установки выполняет обязательные шаги перед первым запуском CMS.</p>
    <ol>
        <?php foreach ($steps as $code => $name): ?>
            <li><strong><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong> <small>(<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>)</small></li>
        <?php endforeach; ?>
    </ol>

    <form method="post" action="/install">
        <input type="hidden" name="action" value="complete-install">
        <button type="submit">Завершить установку (MVP)</button>
    </form>

    <p><strong>Важно:</strong> после установки удалите или отключите каталог <code>/install/</code>.</p>
</div>
</body>
</html>
