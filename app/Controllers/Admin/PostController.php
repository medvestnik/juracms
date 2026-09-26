<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\CategoryModel;
use App\Models\LocaleModel;
use App\Models\PostModel;
use App\Models\RouteModel;
use PDO;

final class PostController
{
    public static function reorder(PDO $pdo): never
    {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (is_array($ids)) {
            PostModel::reorder($pdo, $ids);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public static function index(PDO $pdo): never
    {
        $sortMap = ['date' => 'p.published_at', 'title' => 'p.title', 'id' => 'p.id', 'order' => 'p.sort_order'];
        $sort = array_key_exists($_GET['sort'] ?? '', $sortMap) ? $_GET['sort'] : 'date';
        $dir = ($sort === 'order') ? 'ASC' : ((($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC');
        $perPageOpts = [20, 25, 30, 50, 100, 200];
        $perPage = in_array((int) ($_GET['per_page'] ?? 20), $perPageOpts) ? (int) ($_GET['per_page'] ?? 20) : 20;
        $total = PostModel::count($pdo);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $curPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset = ($curPage - 1) * $perPage;
        view_admin('posts', [
            'title' => 'Публікації',
            'posts' => PostModel::paginate($pdo, $sortMap[$sort], $dir, $perPage, $offset),
            'sort' => $sort,
            'dir' => $dir,
            'per_page' => $perPage,
            'per_page_opts' => $perPageOpts,
            'cur_page' => $curPage,
            'total_pages' => $totalPages,
            'total' => $total,
        ]);
        exit;
    }

    public static function create(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $status = (string) ($_POST['status'] ?? 'draft');
            $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : ($status === 'published' ? date('Y-m-d H:i:s') : null);
            [, $defaultLocale] = LocaleModel::active($pdo);
            $newPostId = PostModel::create($pdo, [
                'slug' => $slug,
                'title' => $_POST['title'],
                'excerpt' => $_POST['excerpt'] ?? '',
                'content' => $_POST['content'] ?? '',
                'status' => $status,
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'published_at' => $publishedAt,
                'locale' => $defaultLocale,
            ]);
            RouteModel::save($pdo, '/blog/' . $slug, 'post', $newPostId);
            PostModel::addCategory($pdo, $newPostId, (int) ($_POST['category_id'] ?? 0));
            redirect(isset($_POST['_close']) ? '/admin/posts' : '/admin/posts/' . $newPostId . '/edit');
        }
        view_admin('post-edit', ['title' => 'Нова публікація', 'post' => [], 'categories' => CategoryModel::options($pdo)]);
        exit;
    }

    public static function translate(PDO $pdo, int $sourceId): never
    {
        $locale = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['locale'] ?? '')));
        $source = PostModel::find($pdo, $sourceId);
        if ($source && $locale !== '') {
            $rootId = RouteModel::translationRootId($source);
            $newId = PostModel::findTranslationId($pdo, $rootId, $locale);
            if (!$newId) {
                $newId = PostModel::createTranslation($pdo, $source, $locale, $rootId);
                [, $defaultLocale] = LocaleModel::active($pdo);
                $routePrefix = $locale !== $defaultLocale ? '/' . $locale : '';
                RouteModel::save($pdo, $routePrefix . '/blog/' . $source['slug'], 'post', $newId);
            }
            redirect('/admin/posts/' . $newId . '/edit');
        }
        redirect('/admin/posts/' . $sourceId . '/edit');
    }

    public static function edit(PDO $pdo, int $id, string $method): never
    {
        $post = PostModel::find($pdo, $id);
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $status = (string) ($_POST['status'] ?? 'draft');
            $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : ($status === 'published' ? date('Y-m-d H:i:s') : null);
            PostModel::update($pdo, $id, [
                'slug' => $slug,
                'title' => $_POST['title'],
                'excerpt' => $_POST['excerpt'] ?? '',
                'content' => $_POST['content'] ?? '',
                'status' => $status,
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'published_at' => $publishedAt,
                'featured_image' => $post['featured_image'] ?? null,
            ]);
            [, $defaultLocale] = LocaleModel::active($pdo);
            $postLocale = (string) ($post['locale'] ?? $defaultLocale);
            $routePrefix = $postLocale !== '' && $postLocale !== $defaultLocale ? '/' . $postLocale : '';
            RouteModel::deleteFor($pdo, 'post', $id);
            RouteModel::save($pdo, $routePrefix . '/blog/' . $slug, 'post', $id);
            PostModel::setCategory($pdo, $id, (int) ($_POST['category_id'] ?? 0));
            redirect(isset($_POST['_close']) ? '/admin/posts' : '/admin/posts/' . $id . '/edit');
        }
        if ($post) {
            $post['content'] = str_replace(['src="/userfiles/', "src='/userfiles/"], ['src="/public/userfiles/', "src='/public/userfiles/"], $post['content'] ?? '');
            $post['category_id'] = PostModel::categoryIdFor($pdo, (int) $post['id']);
        }
        view_admin('post-edit', [
            'title' => 'Редагувати публікацію',
            'post' => $post,
            'categories' => CategoryModel::options($pdo),
            'translations' => $post ? RouteModel::entityTranslations($pdo, 'posts', $post) : [],
            'all_locales' => $post ? jura_available_locales($pdo) : [],
        ]);
        exit;
    }

    public static function uploadImage(PDO $pdo, int $id): never
    {
        $uploadDir = BASE_PATH . '/public/userfiles/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $file = $_FILES['featured_image'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $filename = 'post-' . $id . '-' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $post = PostModel::find($pdo, $id);
                    $oldImg = $post['featured_image'] ?? null;
                    if ($oldImg && str_starts_with($oldImg, 'post-') && file_exists($uploadDir . $oldImg)) {
                        @unlink($uploadDir . $oldImg);
                    }
                    PostModel::updateFeaturedImage($pdo, $id, $filename);
                }
            }
        } elseif (!empty($_POST['remove_image'])) {
            PostModel::updateFeaturedImage($pdo, $id, null);
        }
        redirect('/admin/posts/' . $id . '/edit');
    }

    public static function delete(PDO $pdo, int $id): never
    {
        PostModel::delete($pdo, $id);
        redirect('/admin/posts');
    }

    public static function toggle(PDO $pdo, int $id): never
    {
        PostModel::toggleStatus($pdo, $id);
        redirect('/admin/posts');
    }
}
