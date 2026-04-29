<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/core/start.php';

use Core\Installer\Runtime as InstallerRuntime;
use Core\Updater\Updater;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!InstallerRuntime::isInstalled()) {
    if (str_starts_with($path, '/admin')) {
        redirect('/install/');
    }

    if ($path !== '/install' && $path !== '/install/') {
        redirect('/install/');
    }
}

if ($method !== 'GET' && !in_array($path, ['/admin/system/updates/run', '/admin/login', '/admin/logout'], true)) {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

switch (rtrim($path, '/') ?: '/') {
    case '/':
        view_frontend('home', ['title' => 'Jura CMS']);
        break;

    case '/admin/login':
        if (!InstallerRuntime::isInstalled()) {
            redirect('/install/');
        }

        if ($method === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                session_flash('auth_error', 'Введите email и пароль.');
                redirect('/admin/login');
            }

            $lock = json_decode((string) @file_get_contents(InstallerRuntime::lockFile()), true);
            $dbConfig = (array) ($lock['database'] ?? []);

            try {
                $pdo = db_connect($dbConfig);
                $prefix = (string) ($dbConfig['prefix'] ?? 'jura_');
                $table = sprintf('`%sadmins`', str_replace('`', '', $prefix));
                $stmt = $pdo->prepare("SELECT id, email, password_hash FROM {$table} WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $admin = $stmt->fetch();

                if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
                    session_flash('auth_error', 'Неверный email или пароль.');
                    redirect('/admin/login');
                }

                $_SESSION['admin_user_id'] = (int) $admin['id'];
                $_SESSION['admin_user_email'] = (string) $admin['email'];
                redirect('/admin');
            } catch (Throwable $e) {
                session_flash('auth_error', 'Ошибка авторизации: ' . $e->getMessage());
                redirect('/admin/login');
            }
        }

        if (admin_is_authenticated()) {
            redirect('/admin');
        }

        view_admin('login', [
            'title' => 'Sign in',
            'layout' => 'auth',
            'error' => session_flash('auth_error'),
        ]);
        break;

    case '/admin/logout':
        if ($method !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }
        unset($_SESSION['admin_user_id'], $_SESSION['admin_user_email']);
        session_flash('auth_success', 'Вы вышли из админки.');
        redirect('/admin/login');

    case '/admin':
        admin_require_auth();
        view_admin('dashboard', ['title' => 'Dashboard']);
        break;

    case '/admin/pages':
        admin_require_auth();
        view_admin('pages/index', ['title' => 'Pages']);
        break;

    case '/admin/pages/edit':
        admin_require_auth();
        view_admin('pages/editor', ['title' => 'Edit page']);
        break;

    case '/admin/articles/edit':
        admin_require_auth();
        view_admin('pages/editor', ['title' => 'Edit article', 'entity' => 'article']);
        break;

    case '/admin/blocks/edit':
        admin_require_auth();
        view_admin('pages/editor', ['title' => 'Edit block', 'entity' => 'block']);
        break;

    case '/admin/media':
        admin_require_auth();
        view_admin('media/index', ['title' => 'Media']);
        break;

    case '/admin/settings':
        admin_require_auth();
        view_admin('settings/index', ['title' => 'Settings']);
        break;

    case '/admin/system/updates':
        admin_require_auth();
        view_admin('updates/index', [
            'title' => 'System Updates',
            'update' => Updater::checkForUpdates(),
            'requiresFinalize' => Updater::needsManualFinalize(),
        ]);
        break;

    case '/admin/system/updates/run':
        admin_require_auth();
        Updater::finalizeManualUpdate();
        view_admin('updates/index', [
            'title' => 'System Updates',
            'update' => Updater::runAutomaticUpdate(),
            'requiresFinalize' => false,
            'flash' => 'Обновление запущено: миграции и очистка кэша помечены как выполненные (MVP).',
        ]);
        break;

    case '/install':
        view_admin('install', [
            'title' => 'Installer is disabled',
            'layout' => 'installer',
            'warning' => 'Jura CMS уже установлена. Повторная установка заблокирована. Удалите /install для безопасности.',
        ]);
        break;

    default:
        http_response_code(404);
        view_frontend('page', [
            'title' => 'Page not found',
            'message' => 'The requested page was not found.',
        ]);
        break;
}
