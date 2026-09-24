<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\BlockRegistry;
use App\Models\LocaleModel;
use App\Models\PageBlockModel;
use App\Models\PageModel;
use App\Models\RouteModel;
use PDO;

final class PageController
{
    public static function index(PDO $pdo): never
    {
        $sortMap = ['date' => 'p.updated_at', 'title' => 'p.title', 'id' => 'p.id', 'order' => 'p.sort_order'];
        $sort = array_key_exists($_GET['sort'] ?? '', $sortMap) ? $_GET['sort'] : 'order';
        $dir = ($sort === 'order') ? 'ASC' : ((($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC');
        $perPageOpts = [20, 25, 30, 50, 100, 200];
        $perPage = in_array((int) ($_GET['per_page'] ?? 20), $perPageOpts) ? (int) ($_GET['per_page'] ?? 20) : 20;
        $total = PageModel::count($pdo);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $curPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset = ($curPage - 1) * $perPage;
        view_admin('pages', [
            'title' => 'Сторінки',
            'pages' => PageModel::paginate($pdo, $sortMap[$sort], $dir, $perPage, $offset),
            'edit' => null,
            'sort' => $sort,
            'dir' => $dir,
            'per_page' => $perPage,
            'per_page_opts' => $perPageOpts,
            'cur_page' => $curPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'template_options' => page_template_options(),
        ]);
        exit;
    }

    public static function reorder(PDO $pdo): never
    {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (is_array($ids)) {
            PageModel::reorder($pdo, $ids);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public static function create(): never
    {
        view_admin('pages', ['title' => 'Додати сторінку', 'edit' => [], 'template_options' => page_template_options()]);
        exit;
    }

    public static function store(PDO $pdo): never
    {
        $status = (string) ($_POST['status'] ?? 'draft');
        $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
        $route = ensure_path((string) ($_POST['route_path'] ?? ('/' . $slug)));
        if ($route === '/home') {
            $route = '/';
        }
        [, $defaultLocale] = LocaleModel::active($pdo);
        $id = PageModel::create($pdo, [
            'author_id' => (int) $_SESSION['admin_user_id'],
            'title' => $_POST['title'],
            'slug' => $slug,
            'content' => $_POST['content'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'status' => $status,
            'template' => $_POST['template'] ?? 'page',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? '',
            'canonical_path' => $_POST['canonical_path'] ?? '',
            'og_title' => $_POST['og_title'] ?? '',
            'og_description' => $_POST['og_description'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'locale' => $defaultLocale,
        ]);
        RouteModel::save($pdo, $route, 'page', $id);
        redirect(isset($_POST['_close']) ? '/admin/pages' : '/admin/pages/' . $id . '/edit');
    }

    public static function edit(PDO $pdo, int $id): never
    {
        $editPage = PageModel::findWithRoute($pdo, $id) ?: [];
        $localeRows = $editPage ? jura_available_locales($pdo) : [];
        view_admin('pages', [
            'title' => 'Редагувати сторінку',
            'edit' => $editPage,
            'translations' => $editPage ? RouteModel::entityTranslations($pdo, 'pages', $editPage) : [],
            'all_locales' => $localeRows,
            'template_options' => page_template_options(),
            'blocks' => $editPage ? page_blocks($pdo, (int) $editPage['id']) : [],
            'block_types' => BlockRegistry::labels(),
        ]);
        exit;
    }

    public static function update(PDO $pdo, int $id): never
    {
        $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
        $route = ensure_path((string) ($_POST['route_path'] ?? ('/' . $slug)));
        PageModel::update($pdo, $id, [
            'title' => $_POST['title'],
            'slug' => $slug,
            'content' => $_POST['content'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'template' => $_POST['template'] ?? 'page',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? '',
            'canonical_path' => $_POST['canonical_path'] ?? '',
            'og_title' => $_POST['og_title'] ?? '',
            'og_description' => $_POST['og_description'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        RouteModel::deleteFor($pdo, 'page', $id);
        RouteModel::save($pdo, $route, 'page', $id);
        redirect(isset($_POST['_close']) ? '/admin/pages' : '/admin/pages/' . $id . '/edit');
    }

    public static function delete(PDO $pdo, int $id): never
    {
        RouteModel::deleteFor($pdo, 'page', $id);
        PageModel::delete($pdo, $id);
        redirect('/admin/pages');
    }

    public static function toggle(PDO $pdo, int $id): never
    {
        PageModel::toggleStatus($pdo, $id);
        redirect('/admin/pages');
    }

    public static function translate(PDO $pdo, int $sourceId): never
    {
        $locale = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['locale'] ?? '')));
        $source = PageModel::find($pdo, $sourceId);
        if ($source && $locale !== '') {
            $rootId = RouteModel::translationRootId($source);
            $newId = PageModel::findTranslationId($pdo, $rootId, $locale);
            if (!$newId) {
                $newId = PageModel::createTranslation($pdo, $source, $locale, $rootId, (int) $_SESSION['admin_user_id']);
                // The home page's translation gets its locale's root URL
                // (/en) rather than /en/home -- home is always "/" in the
                // default locale, so its translations follow the same rule.
                $routePath = $source['template'] === 'home' ? '/' . $locale : '/' . $locale . '/' . $source['slug'];
                RouteModel::save($pdo, $routePath, 'page', $newId);
            }
            redirect('/admin/pages/' . $newId . '/edit');
        }
        redirect('/admin/pages/' . $sourceId . '/edit');
    }

    // ── Blocks ───────────────────────────────────────────────────────────

    public static function addBlock(PDO $pdo, int $pageId): never
    {
        $type = (string) ($_POST['block_type'] ?? '');
        if (BlockRegistry::has($type)) {
            PageBlockModel::add($pdo, $pageId, $type);
        }
        redirect('/admin/pages/' . $pageId . '/edit#blocks');
    }

    public static function updateBlock(PDO $pdo, int $pageId, int $blockId): never
    {
        $block = PageBlockModel::find($pdo, $blockId, $pageId);
        if ($block) {
            $current = json_decode((string) ($block['settings_json'] ?? '{}'), true) ?: [];
            $settings = BlockRegistry::saveSettings((string) $block['block_type'], $_POST, $current);
            PageBlockModel::updateSettings($pdo, $blockId, $settings);
        }
        redirect('/admin/pages/' . $pageId . '/edit#blocks');
    }

    public static function uploadBlockImage(PDO $pdo, int $pageId, int $blockId): never
    {
        $block = PageBlockModel::find($pdo, $blockId, $pageId);
        if ($block) {
            $settings = json_decode((string) ($block['settings_json'] ?? '{}'), true) ?: [];
            $uploadDir = BASE_PATH . '/public/userfiles/blocks/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file = $_FILES['image'] ?? null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $filename = 'block-' . $blockId . '-' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $oldImg = $settings['image'] ?? null;
                        if ($oldImg && str_starts_with((string) $oldImg, 'block-') && file_exists($uploadDir . $oldImg)) {
                            @unlink($uploadDir . $oldImg);
                        }
                        $settings['image'] = $filename;
                    }
                }
            } elseif (!empty($_POST['remove_image'])) {
                $settings['image'] = null;
            }
            PageBlockModel::updateSettings($pdo, $blockId, $settings);
        }
        redirect('/admin/pages/' . $pageId . '/edit#blocks');
    }

    public static function deleteBlock(PDO $pdo, int $pageId, int $blockId): never
    {
        PageBlockModel::delete($pdo, $blockId, $pageId);
        redirect('/admin/pages/' . $pageId . '/edit#blocks');
    }

    public static function reorderBlocks(PDO $pdo, int $pageId): never
    {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (is_array($ids)) {
            PageBlockModel::reorder($pdo, $pageId, $ids);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
