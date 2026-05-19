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

        $dbConfig = (array) cms_config('database', []);
        $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($dbConfig['prefix'] ?? 'jura_')) ?: 'jura_';

        try {
            $pdo = db_connect($dbConfig);
            $table = sprintf('`%sadmins`', str_replace('`', '', $prefix));
            $countStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM {$table}");
            $adminsCount = (int) (($countStmt->fetch()['cnt'] ?? 0));

            if ($adminsCount < 1) {
                view_admin('login', [
                    'title' => 'Sign in',
                    'layout' => 'auth',
                    'error' => 'Администратор не найден. Установка неполная. Перейдите в /install/ и выполните сброс установки.',
                ]);
                break;
            }
        } catch (Throwable $e) {
            view_admin('login', [
                'title' => 'Sign in',
                'layout' => 'auth',
                'error' => 'Ошибка подключения к БД. Перейдите в /install/ и выполните проверку установки.',
            ]);
            break;
        }

        if ($method === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                session_flash('auth_error', 'Введите email и пароль.');
                redirect('/admin/login');
            }

            try {
                $pdo = db_connect($dbConfig);
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

    case '/install':
        require __DIR__ . '/install/index.php';
        break;

    default:
        http_response_code(404);
        view_frontend('page', ['title' => 'Page not found', 'message' => 'The requested page was not found.']);
        break;
}
