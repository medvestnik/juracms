<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', __DIR__);
}

$autoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$coreStart = BASE_PATH . '/core/start.php';
if (file_exists($coreStart)) {
    require_once $coreStart;
}

$lockFile = BASE_PATH . '/storage/installed.lock';
$installed = is_file($lockFile);
$errors = [];

$directories = [
    'storage' => BASE_PATH . '/storage',
    'storage/backups' => BASE_PATH . '/storage/backups',
    'storage/updates' => BASE_PATH . '/storage/updates',
    'uploads' => BASE_PATH . '/uploads',
    'cache' => BASE_PATH . '/cache',
    'logs' => BASE_PATH . '/logs',
];

$diagnosticTargets = [
    'storage' => BASE_PATH . '/storage',
    'uploads' => BASE_PATH . '/uploads',
    'cache' => BASE_PATH . '/cache',
    'logs' => BASE_PATH . '/logs',
];

$steps = [
    'Проверка окружения',
    'Проверка прав на директории',
    'Подключение к базе данных',
    'Генерация .env',
    'Создание администратора',
    'Выполнение миграций',
    'Завершение установки',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['install_action'] ?? '') === 'finish' && !$installed) {
    foreach ($directories as $name => $path) {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            $errors[] = sprintf('Не удалось создать директорию %s (%s).', $name, $path);
            continue;
        }

        if (!is_writable($path)) {
            $errors[] = sprintf('Директория %s недоступна для записи (%s).', $name, $path);
        }
    }

    if ($errors === []) {
        $versionFile = BASE_PATH . '/VERSION';
        $version = '0.1.0-alpha';

        if (is_file($versionFile)) {
            $rawVersion = trim((string) file_get_contents($versionFile));
            if ($rawVersion !== '') {
                $version = $rawVersion;
            }
        }

        $lockPayload = [
            'installed_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'version' => $version,
            'mode' => 'mvp',
        ];

        $written = file_put_contents(
            $lockFile,
            json_encode($lockPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );

        if ($written === false) {
            $errors[] = sprintf('Не удалось записать файл установки (%s).', $lockFile);
        } else {
            header('Location: /');
            exit;
        }
    }
}

$installed = is_file($lockFile);
$hasInstallCss = is_file(INSTALL_PATH . '/assets/installer.css');
$cssHref = $hasInstallCss ? '/install/assets/installer.css' : '/public/assets/cms/installer.css';

$diagnostics = [
    'PHP version' => PHP_VERSION,
    'BASE_PATH' => BASE_PATH,
    'vendor/autoload.php' => is_file($autoload) ? 'exists' : 'missing',
    'installed.lock' => $installed ? 'exists' : 'missing',
];

foreach ($diagnosticTargets as $name => $path) {
    $diagnostics[sprintf('%s writable', $name)] = is_dir($path) ? (is_writable($path) ? 'yes' : 'no') : 'missing';
}

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка Jura CMS</title>
    <?php if ($cssHref !== ''): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
</head>
<body>
<div class="installer-shell">
    <?php if ($installed): ?>
        <h1>Jura CMS уже установлена</h1>
        <p>Повторная установка заблокирована.</p>
        <div class="installer-actions">
            <a class="installer-link" href="/">Открыть сайт</a>
            <a class="installer-link" href="/admin">Перейти в админку</a>
        </div>
        <p class="installer-warning">Для безопасности удалите или отключите каталог <code>/install/</code>.</p>
    <?php else: ?>
        <h1>Установка Jura CMS (MVP)</h1>
        <p>Мастер подготовит структуру проекта и создаст файл <code>storage/installed.lock</code>.</p>

        <?php if ($errors !== []): ?>
            <div class="installer-error">
                <strong>Установка не завершена:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h2>Шаги мастера</h2>
        <ol>
            <?php foreach ($steps as $step): ?>
                <li><?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ol>

        <form method="post" action="/install/">
            <input type="hidden" name="install_action" value="finish">
            <button type="submit">Завершить установку (MVP)</button>
        </form>

        <h2>Диагностика</h2>
        <dl class="installer-diagnostics">
            <?php foreach ($diagnostics as $key => $value): ?>
                <div>
                    <dt><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></dt>
                    <dd><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>
</div>
</body>
</html>
