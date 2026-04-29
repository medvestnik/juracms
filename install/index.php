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
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
$writableDirectories = [
    'storage/' => BASE_PATH . '/storage',
    'storage/backups/' => BASE_PATH . '/storage/backups',
    'storage/updates/' => BASE_PATH . '/storage/updates',
    'uploads/' => BASE_PATH . '/uploads',
    'cache/' => BASE_PATH . '/cache',
    'logs/' => BASE_PATH . '/logs',
];

$environmentChecks = [
    'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'vendor/autoload.php' => is_file($autoload),
];

foreach ($requiredExtensions as $ext) {
    $environmentChecks['extension: ' . $ext] = extension_loaded($ext);
}

foreach ($writableDirectories as $label => $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $environmentChecks['writable: ' . $label] = is_dir($dir) && is_writable($dir);
}

$environmentOk = !in_array(false, $environmentChecks, true);

if (!isset($_SESSION['installer'])) {
    $_SESSION['installer'] = [];
}

$errors = [];
$step = max(1, (int) ($_SESSION['installer']['step'] ?? 1));
$db = (array) ($_SESSION['installer']['db'] ?? [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => '',
    'username' => '',
    'password' => '',
    'prefix' => 'jura_',
]);
$admin = (array) ($_SESSION['installer']['admin'] ?? [
    'site_name' => '',
    'admin_name' => '',
    'admin_email' => '',
]);

if ($installed && $method === 'POST') {
    http_response_code(409);
    $errors[] = 'Jura CMS уже установлена. Повторная установка запрещена.';
}

if (!$installed && $method === 'POST') {
    $action = (string) ($_POST['install_action'] ?? '');

    if ($action === 'environment-next') {
        if (!$environmentOk) {
            $errors[] = 'Сервер не прошёл обязательные проверки окружения.';
            $step = 1;
        } else {
            $_SESSION['installer']['step'] = 2;
            $step = 2;
        }
    }

    if ($action === 'db-test') {
        $db = [
            'host' => trim((string) ($_POST['db_host'] ?? '')),
            'port' => trim((string) ($_POST['db_port'] ?? '3306')),
            'database' => trim((string) ($_POST['db_database'] ?? '')),
            'username' => trim((string) ($_POST['db_username'] ?? '')),
            'password' => (string) ($_POST['db_password'] ?? ''),
            'prefix' => trim((string) ($_POST['db_prefix'] ?? 'jura_')),
        ];

        if ($db['host'] === '' || $db['database'] === '' || $db['username'] === '') {
            $errors[] = 'Заполните DB_HOST, DB_DATABASE и DB_USERNAME.';
            $step = 2;
        } else {
            try {
                db_connect($db);
                $_SESSION['installer']['db'] = $db;
                $_SESSION['installer']['step'] = 3;
                $step = 3;
                session_flash('installer_success', 'Подключение к базе данных успешно проверено.');
            } catch (Throwable $e) {
                $errors[] = 'Ошибка подключения к базе данных: ' . $e->getMessage();
                $step = 2;
            }
        }
    }

    if ($action === 'admin-next') {
        $admin = [
            'site_name' => trim((string) ($_POST['site_name'] ?? '')),
            'admin_name' => trim((string) ($_POST['admin_name'] ?? '')),
            'admin_email' => trim((string) ($_POST['admin_email'] ?? '')),
        ];
        $password = (string) ($_POST['admin_password'] ?? '');
        $passwordConfirmation = (string) ($_POST['admin_password_confirmation'] ?? '');

        if ($admin['site_name'] === '') {
            $errors[] = 'Поле site_name обязательно.';
        }
        if ($admin['admin_name'] === '') {
            $errors[] = 'Поле admin_name обязательно.';
        }
        if ($admin['admin_email'] === '' || !filter_var($admin['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Укажите корректный admin_email.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Пароль администратора должен быть минимум 8 символов.';
        }
        if ($password !== $passwordConfirmation) {
            $errors[] = 'Подтверждение пароля не совпадает.';
        }

        if ($errors === []) {
            $_SESSION['installer']['admin'] = $admin;
            $_SESSION['installer']['admin_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $_SESSION['installer']['step'] = 4;
            $step = 4;
        } else {
            $step = 3;
        }
    }

    if ($action === 'install-run') {
        $db = (array) ($_SESSION['installer']['db'] ?? []);
        $admin = (array) ($_SESSION['installer']['admin'] ?? []);
        $adminPasswordHash = (string) ($_SESSION['installer']['admin_password_hash'] ?? '');

        if ($db === [] || $admin === [] || $adminPasswordHash === '') {
            $errors[] = 'Сессия установки неполная. Повторите шаги 2 и 3.';
            $step = 2;
        } else {
            try {
                $pdo = db_connect($db);
                $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($db['prefix'] ?? 'jura_')) ?: 'jura_';

                $envValues = [
                    'APP_NAME' => $admin['site_name'],
                    'APP_ENV' => 'production',
                    'APP_DEBUG' => 'false',
                    'APP_URL' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => $db['host'],
                    'DB_PORT' => $db['port'],
                    'DB_DATABASE' => $db['database'],
                    'DB_USERNAME' => $db['username'],
                    'DB_PASSWORD' => $db['password'],
                    'DB_PREFIX' => $prefix,
                ];

                $envContent = '';
                foreach ($envValues as $key => $value) {
                    $envContent .= $key . '="' . str_replace('"', '\\"', (string) $value) . '"' . PHP_EOL;
                }

                if (file_put_contents(BASE_PATH . '/.env', $envContent) === false) {
                    throw new RuntimeException('Не удалось записать файл .env');
                }

                $adminsTable = sprintf('`%sadmins`', $prefix);
                $settingsTable = sprintf('`%ssettings`', $prefix);

                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS {$adminsTable} (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(191) NOT NULL,
                        email VARCHAR(191) NOT NULL UNIQUE,
                        password_hash VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );

                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS {$settingsTable} (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        setting_key VARCHAR(191) NOT NULL UNIQUE,
                        setting_value TEXT NULL,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );

                $stmt = $pdo->prepare("SELECT id FROM {$adminsTable} WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $admin['admin_email']]);
                $existingAdmin = $stmt->fetch();

                if (!$existingAdmin) {
                    $insertAdmin = $pdo->prepare("INSERT INTO {$adminsTable} (name, email, password_hash) VALUES (:name, :email, :password_hash)");
                    $insertAdmin->execute([
                        'name' => $admin['admin_name'],
                        'email' => $admin['admin_email'],
                        'password_hash' => $adminPasswordHash,
                    ]);
                    $adminId = (int) $pdo->lastInsertId();
                } else {
                    $adminId = (int) $existingAdmin['id'];
                }

                $version = trim((string) @file_get_contents(BASE_PATH . '/VERSION')) ?: '0.0.0';
                $setVersion = $pdo->prepare(
                    "INSERT INTO {$settingsTable} (setting_key, setting_value)
                     VALUES ('cms_version', :version)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );
                $setVersion->execute(['version' => $version]);

                $lockPayload = [
                    'installed_at' => (new DateTimeImmutable())->format(DATE_ATOM),
                    'version' => $version,
                    'mode' => 'web-installer',
                    'site_name' => $admin['site_name'],
                    'admin_email' => $admin['admin_email'],
                    'database' => $db,
                ];

                if (file_put_contents($lockFile, json_encode($lockPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL) === false) {
                    throw new RuntimeException('Не удалось создать installed.lock.');
                }

                $_SESSION['admin_user_id'] = $adminId;
                $_SESSION['admin_user_email'] = $admin['admin_email'];
                unset($_SESSION['installer']);
                unset($_SESSION['admin_user_id'], $_SESSION['admin_user_email']);
                redirect('/admin/login');
            } catch (Throwable $e) {
                $errors[] = 'Установка не завершена: ' . $e->getMessage();
                $step = 4;
            }
        }
    }
}

$installed = is_file($lockFile);
$hasInstallCss = is_file(INSTALL_PATH . '/assets/installer.css');
$cssHref = $hasInstallCss ? '/install/assets/installer.css' : '/public/assets/cms/installer.css';
$success = session_flash('installer_success');
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
        <p>Повторная установка заблокирована. POST-запросы установки отключены.</p>
        <div class="installer-actions">
            <a class="installer-link" href="/">Открыть сайт</a>
            <a class="installer-link" href="/admin/login">Вход в админку</a>
        </div>
        <p class="installer-warning">Для безопасности удалите или отключите каталог <code>/install/</code>.</p>
    <?php else: ?>
        <h1>Установка Jura CMS</h1>
        <p>Шаг <?= $step; ?> из 4</p>

        <?php if ($success): ?>
            <div class="installer-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="installer-error">
                <strong>Ошибки:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <h2>Шаг 1. Проверка окружения</h2>
            <dl class="installer-diagnostics">
                <?php foreach ($environmentChecks as $check => $ok): ?>
                    <div>
                        <dt><?= e($check); ?></dt>
                        <dd><?= $ok ? 'OK' : 'FAIL'; ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <form method="post" action="/install/">
                <input type="hidden" name="install_action" value="environment-next">
                <button type="submit" <?= $environmentOk ? '' : 'disabled'; ?>>Продолжить</button>
            </form>
        <?php elseif ($step === 2): ?>
            <h2>Шаг 2. Настройка базы данных</h2>
            <form method="post" action="/install/">
                <input type="hidden" name="install_action" value="db-test">
                <p><label>DB_HOST <input name="db_host" value="<?= e($db['host'] ?? ''); ?>"></label></p>
                <p><label>DB_PORT <input name="db_port" value="<?= e($db['port'] ?? '3306'); ?>"></label></p>
                <p><label>DB_DATABASE <input name="db_database" value="<?= e($db['database'] ?? ''); ?>"></label></p>
                <p><label>DB_USERNAME <input name="db_username" value="<?= e($db['username'] ?? ''); ?>"></label></p>
                <p><label>DB_PASSWORD <input type="password" name="db_password" value="<?= e($db['password'] ?? ''); ?>"></label></p>
                <p><label>DB_PREFIX <input name="db_prefix" value="<?= e($db['prefix'] ?? 'jura_'); ?>"></label></p>
                <button type="submit">Проверить подключение</button>
            </form>
        <?php elseif ($step === 3): ?>
            <h2>Шаг 3. Создание администратора</h2>
            <form method="post" action="/install/">
                <input type="hidden" name="install_action" value="admin-next">
                <p><label>site_name <input name="site_name" value="<?= e($admin['site_name'] ?? ''); ?>" required></label></p>
                <p><label>admin_name <input name="admin_name" value="<?= e($admin['admin_name'] ?? ''); ?>" required></label></p>
                <p><label>admin_email <input type="email" name="admin_email" value="<?= e($admin['admin_email'] ?? ''); ?>" required></label></p>
                <p><label>admin_password <input type="password" name="admin_password" required></label></p>
                <p><label>admin_password_confirmation <input type="password" name="admin_password_confirmation" required></label></p>
                <button type="submit">Продолжить</button>
            </form>
        <?php else: ?>
            <h2>Шаг 4. Выполнение установки</h2>
            <p>Будут созданы/обновлены .env, таблицы и администратор. installed.lock появится только после успешного завершения.</p>
            <form method="post" action="/install/">
                <input type="hidden" name="install_action" value="install-run">
                <button type="submit">Завершить установку</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
