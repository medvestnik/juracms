<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
require_once __DIR__ . '/core/start.php';

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LocaleController;
use App\Controllers\Admin\MaintenanceController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\ModuleController;
use App\Controllers\Admin\PageController;
use App\Controllers\Admin\PostController;
use App\Controllers\Admin\RedirectController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ThemeController;
use App\Controllers\Admin\UpdateController;
use App\Controllers\Admin\UserController;
use App\Controllers\Frontend\FrontendController;
use App\Core\ModuleLoader;
use App\Core\Schema;
use Core\Installer\Runtime as InstallerRuntime;

function admin_db(): PDO
{
    return db_connect((array) cms_config('database', []));
}

function normalize_admin_path(string $path): string
{
    return rtrim($path, '/') ?: '/';
}

$path = normalize_admin_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Whole-page cache for anonymous frontend GETs -- served straight off disk,
// before even connecting to the database, when there's a hit.
$frontendCacheKey = null;
if ($method === 'GET' && !str_starts_with($path, '/admin') && !str_starts_with($path, '/install') && !str_starts_with($path, '/forms')) {
    $frontendCacheKey = cache_key_for_request($path, (string) ($_SERVER['QUERY_STRING'] ?? ''));
    $cached = cache_get($frontendCacheKey);
    if ($cached !== null) {
        echo $cached;
        exit;
    }
}

if (!InstallerRuntime::isInstalled() && !in_array($path, ['/install', '/install/'], true)) {
    redirect('/install/');
}
if ($method !== 'GET' && !str_starts_with($path, '/admin') && !str_starts_with($path, '/forms') && !str_starts_with($path, '/install')) {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}
if ($path === '/admin/page') {
    redirect('/admin/pages');
}

if ($path === '/admin/login') {
    AuthController::login($method);
}
if ($path === '/admin/logout') {
    AuthController::logout($method);
}

if (str_starts_with($path, '/admin')) {
    admin_require_auth();
    $pdo = admin_db();
    // Any admin write can change what the frontend should show -- flush the
    // whole page cache rather than trying to track which cached paths a
    // given save affects. Cheap (a POST is rare next to page views) and
    // never leaves stale content behind.
    if ($method === 'POST') {
        cache_clear();
    }
    Schema::ensure($pdo);
    ModuleLoader::ensureTable($pdo);
    ModuleLoader::autoMigrate($pdo);
    ModuleLoader::loadInstalled($pdo);
    ModuleLoader::ensureSchemaOnce($pdo);

    if ($path === '/admin') {
        DashboardController::index($pdo);
    }

    if ($path === '/admin/settings') {
        SettingsController::index($pdo, $method);
    }
    if ($path === '/admin/update-library' && $method === 'POST') {
        SettingsController::updateLibrary($pdo);
    }

    if ($path === '/admin/pages' && $method === 'GET') {
        PageController::index($pdo);
    }
    if ($path === '/admin/pages/reorder' && $method === 'POST') {
        PageController::reorder($pdo);
    }
    if ($path === '/admin/pages/create' && $method === 'GET') {
        PageController::create();
    }
    if (preg_match('#^/admin/pages/(\d+)/blocks/add$#', $path, $m) && $method === 'POST') {
        PageController::addBlock($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/pages/(\d+)/blocks/(\d+)/update$#', $path, $m) && $method === 'POST') {
        PageController::updateBlock($pdo, (int) $m[1], (int) $m[2]);
    }
    if (preg_match('#^/admin/pages/(\d+)/blocks/(\d+)/upload-image$#', $path, $m) && $method === 'POST') {
        PageController::uploadBlockImage($pdo, (int) $m[1], (int) $m[2]);
    }
    if (preg_match('#^/admin/pages/(\d+)/blocks/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        PageController::deleteBlock($pdo, (int) $m[1], (int) $m[2]);
    }
    if (preg_match('#^/admin/pages/(\d+)/blocks/reorder$#', $path, $m) && $method === 'POST') {
        PageController::reorderBlocks($pdo, (int) $m[1]);
    }
    if ($path === '/admin/pages' && $method === 'POST') {
        PageController::store($pdo);
    }
    if (preg_match('#^/admin/pages/(\d+)/edit$#', $path, $m) && $method === 'GET') {
        PageController::edit($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/pages/(\d+)/translate$#', $path, $m) && $method === 'POST') {
        PageController::translate($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/pages/(\d+)$#', $path, $m) && $method === 'POST') {
        PageController::update($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/pages/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        PageController::delete($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/pages/(\d+)/toggle$#', $path, $m) && $method === 'POST') {
        PageController::toggle($pdo, (int) $m[1]);
    }

    if ($path === '/admin/media') {
        MediaController::index($pdo);
    }
    if ($path === '/admin/media/upload' && $method === 'POST') {
        MediaController::upload($pdo);
    }

    if ($path === '/admin/menus') {
        MenuController::index($pdo, $method);
    }

    if ($path === '/admin/redirects') {
        RedirectController::index($pdo, $method);
    }

    if ($path === '/admin/modules') {
        ModuleController::index($pdo, $method);
    }

    if ($path === '/admin/locales') {
        LocaleController::index($pdo, $method);
    }

    if (ModuleLoader::hookFirst('handle_admin', $path, $method, $pdo)) {
        exit;
    }

    if ($path === '/admin/themes' || preg_match('#^/admin/themes/([a-z0-9_-]+)$#', $path, $tm)) {
        ThemeController::index($tm[1] ?? null, $method);
    }

    if ($path === '/admin/maintenance') {
        MaintenanceController::index($pdo, $method);
    }

    if ($path === '/admin/updates') {
        UpdateController::index($method);
    }

    if ($path === '/admin/posts/reorder' && $method === 'POST') {
        PostController::reorder($pdo);
    }
    if ($path === '/admin/posts') {
        PostController::index($pdo);
    }
    if ($path === '/admin/posts/create') {
        PostController::create($pdo, $method);
    }
    if (preg_match('#^/admin/posts/(\d+)/translate$#', $path, $m) && $method === 'POST') {
        PostController::translate($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/posts/(\d+)/edit$#', $path, $m)) {
        PostController::edit($pdo, (int) $m[1], $method);
    }
    if (preg_match('#^/admin/posts/(\d+)/upload-image$#', $path, $m) && $method === 'POST') {
        PostController::uploadImage($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/posts/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        PostController::delete($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/posts/(\d+)/toggle$#', $path, $m) && $method === 'POST') {
        PostController::toggle($pdo, (int) $m[1]);
    }

    if ($path === '/admin/categories') {
        CategoryController::index($pdo, $method);
    }

    if ($path === '/admin/users') {
        UserController::index($pdo);
    }
    if ($path === '/admin/users/create' && $method === 'POST') {
        UserController::create($pdo);
    }
    if (preg_match('#^/admin/users/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        UserController::delete($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/users/(\d+)/password$#', $path, $m) && $method === 'POST') {
        UserController::updatePassword($pdo, (int) $m[1]);
    }
    if (preg_match('#^/admin/users/(\d+)/toggle$#', $path, $m) && $method === 'POST') {
        UserController::toggle($pdo, (int) $m[1]);
    }

    http_response_code(404);
    echo 'Not Found';
    exit;
}

$pdo = admin_db();
Schema::ensure($pdo);
ModuleLoader::ensureTable($pdo);
ModuleLoader::loadInstalled($pdo);
ModuleLoader::ensureSchemaOnce($pdo);

FrontendController::dispatch($pdo, $path, $method, (string) $frontendCacheKey);
