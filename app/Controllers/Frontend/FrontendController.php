<?php

declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\ModuleLoader;
use App\Models\RedirectModel;
use App\Models\RouteModel;
use App\Models\SettingModel;
use PDO;

/** Top-level frontend dispatch: form submissions, redirects, module frontend
 * hooks, then the page/post a route resolves to, falling back to 404. */
final class FrontendController
{
    public static function dispatch(PDO $pdo, string $path, string $method, string $frontendCacheKey): never
    {
        if ($method === 'POST' && preg_match('#^/forms/([a-zA-Z0-9_-]+)$#', $path, $matches)) {
            FormController::submit($pdo, $matches[1]);
        }

        $redirect = RedirectModel::findActive($pdo, $path);
        if ($redirect) {
            http_response_code((int) $redirect['status_code']);
            header('Location: ' . $redirect['target_path']);
            exit;
        }

        if (ModuleLoader::hookFirst('handle_frontend', $path, $pdo)) {
            exit;
        }

        $route = RouteModel::find($pdo, $path);
        if ($route) {
            if ($route['entity_type'] === 'page') {
                PageRenderController::renderPage($pdo, $route, $frontendCacheKey);
            }
            if ($route['entity_type'] === 'post') {
                PageRenderController::renderPost($pdo, $route, $frontendCacheKey);
            }
        }

        http_response_code(404);
        frontend_render_cached($frontendCacheKey, fn() => view_frontend('404', ['title' => '404', 'settings' => SettingModel::all($pdo)]));
        exit;
    }
}
