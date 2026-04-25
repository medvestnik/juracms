<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap/app.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method !== 'GET') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

switch (rtrim($path, '/') ?: '/') {
    case '/':
        view_frontend('home', [
            'title' => 'Jura CMS',
        ]);
        break;

    case '/admin':
        // TODO: implement authentication
        view_admin('dashboard', [
            'title' => 'Dashboard',
        ]);
        break;

    case '/admin/login':
        view_admin('login', [
            'title' => 'Sign in',
            'layout' => 'auth',
        ]);
        break;

    case '/admin/pages':
        // TODO: implement pages repository
        view_admin('pages/index', [
            'title' => 'Pages',
        ]);
        break;

    case '/admin/media':
        // TODO: implement media upload
        view_admin('media/index', [
            'title' => 'Media',
        ]);
        break;

    case '/admin/settings':
        view_admin('settings/index', [
            'title' => 'Settings',
        ]);
        break;

    case '/install':
        // TODO: implement installer checks
        view_admin('install', [
            'title' => 'Install Jura CMS',
            'layout' => 'installer',
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
