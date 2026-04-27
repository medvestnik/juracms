<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/start.php';

use Core\Installer\Runtime as InstallerRuntime;
use Core\Updater\Updater;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!InstallerRuntime::isInstalled()) {
    require BASE_PATH . '/install/index.php';
    exit;
}

if ($method !== 'GET' && !($method === 'POST' && $path === '/admin/system/updates/run')) {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

switch (rtrim($path, '/') ?: '/') {
    case '/':
        view_frontend('home', ['title' => 'Jura CMS']);
        break;

    case '/admin':
        view_admin('dashboard', ['title' => 'Dashboard']);
        break;

    case '/admin/login':
        view_admin('login', ['title' => 'Sign in', 'layout' => 'auth']);
        break;

    case '/admin/pages':
        view_admin('pages/index', ['title' => 'Pages']);
        break;

    case '/admin/pages/edit':
        view_admin('pages/editor', ['title' => 'Edit page']);
        break;

    case '/admin/articles/edit':
        view_admin('pages/editor', ['title' => 'Edit article', 'entity' => 'article']);
        break;

    case '/admin/blocks/edit':
        view_admin('pages/editor', ['title' => 'Edit block', 'entity' => 'block']);
        break;

    case '/admin/media':
        view_admin('media/index', ['title' => 'Media']);
        break;

    case '/admin/settings':
        view_admin('settings/index', ['title' => 'Settings']);
        break;

    case '/admin/system/updates':
        view_admin('updates/index', [
            'title' => 'System Updates',
            'update' => Updater::checkForUpdates(),
            'requiresFinalize' => Updater::needsManualFinalize(),
        ]);
        break;

    case '/admin/system/updates/run':
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
            'warning' => 'CMS уже установлена. Повторная установка заблокирована. Удалите /install для безопасности.',
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
