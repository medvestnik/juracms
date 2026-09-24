<?php

declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\ModuleLoader;
use App\Models\PageModel;
use App\Models\PostModel;
use App\Models\RouteModel;
use App\Models\SettingModel;
use PDO;

/** Renders the page/post a resolved route points at, dispatching by
 * page template. Assumes a route has already been resolved via
 * RouteModel::find(); does nothing (returns) if the target isn't published,
 * so the caller can fall through to a 404. */
final class PageRenderController
{
    public static function renderPage(PDO $pdo, array $route, string $frontendCacheKey): void
    {
        $page = PageModel::findPublished($pdo, (int) $route['entity_id']);
        if (!$page) {
            return;
        }
        $settings = SettingModel::all($pdo);
        $page['content'] = ModuleLoader::hookFilter('filter_page_content', $page['content'] ?? '', $settings);
        $locale = (string) ($route['locale'] ?? $page['locale'] ?? $settings['default_locale'] ?? 'uk');
        $translations = RouteModel::entityTranslations($pdo, 'pages', $page);
        $common = ['locale' => $locale, 'translations' => $translations];

        if (($page['template'] ?? '') === 'blog') {
            $perPage = max(1, (int) ($settings['blog_per_page'] ?? 12));
            $totalPosts = PostModel::countPublishedForLocale($pdo, $locale);
            $totalPages = max(1, (int) ceil($totalPosts / $perPage));
            $currentPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
            $offset = ($currentPage - 1) * $perPage;
            $posts = PostModel::paginatePublishedForLocale($pdo, $locale, $perPage, $offset);
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('blog', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'posts' => $posts, 'settings' => $settings, 'pagination' => ['current' => $currentPage, 'total' => $totalPages, 'per_page' => $perPage]], $common)));
        } elseif (($page['template'] ?? '') === 'contacts') {
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('contacts', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common)));
        } elseif (($page['template'] ?? '') === 'home') {
            $homeExtra = ModuleLoader::hookCollect('home_data', $pdo, $locale);
            $pageBlocks = page_blocks($pdo, (int) $page['id']);
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('home', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'blocks' => $pageBlocks, 'settings' => $settings], $common, $homeExtra)));
        } elseif (($page['template'] ?? '') === 'about') {
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('about', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common)));
        } elseif (($page['template'] ?? '') === 'blocks') {
            // Generic block-constructor template -- any page can opt into
            // this from the "Шаблон" dropdown, not just Головна. Unlike
            // home.php there's no legacy hardcoded layout to fall back to,
            // so an empty block list just shows a "add blocks" notice.
            $pageBlocks = page_blocks($pdo, (int) $page['id']);
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('blocks', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'blocks' => $pageBlocks, 'settings' => $settings], $common)));
        } elseif (!ModuleLoader::hookFirst('render_page_template', $page, $settings, $locale, $common, $pdo)) {
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('page', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common)));
        }
        exit;
    }

    public static function renderPost(PDO $pdo, array $route, string $frontendCacheKey): void
    {
        $post = PostModel::findPublished($pdo, (int) $route['entity_id']);
        if (!$post) {
            return;
        }
        $locale = (string) ($route['locale'] ?? $post['locale'] ?? 'uk');
        frontend_render_cached($frontendCacheKey, fn() => view_frontend('post', ['title' => $post['meta_title'] ?: $post['title'], 'meta_description' => $post['meta_description'], 'post' => $post, 'settings' => SettingModel::all($pdo), 'locale' => $locale, 'translations' => RouteModel::entityTranslations($pdo, 'posts', $post)]));
        exit;
    }
}
