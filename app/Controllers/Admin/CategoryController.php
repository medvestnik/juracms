<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\CategoryModel;
use App\Models\LocaleModel;
use PDO;

final class CategoryController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'delete') {
                CategoryModel::delete($pdo, (int) $_POST['id']);
            } elseif ($action === 'translate') {
                $sourceId = (int) ($_POST['id'] ?? 0);
                $locale = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['locale'] ?? '')));
                $source = $sourceId ? CategoryModel::find($pdo, $sourceId) : null;
                if ($source && $locale !== '') {
                    $rootId = (int) ($source['translation_of'] ?: $source['id']);
                    if (!CategoryModel::findTranslationId($pdo, $rootId, $locale)) {
                        try {
                            CategoryModel::createTranslation($pdo, $source, $locale, $rootId);
                        } catch (\PDOException $e) {
                            // Two translate clicks racing each other (e.g. the
                            // same "+locale" action was visible on more than one
                            // row) both pass the check above before either
                            // commits; the loser hits the unique index on
                            // (translation_of,locale) instead of creating a
                            // duplicate. That's exactly what it's there for.
                            if ($e->getCode() !== '23000') {
                                throw $e;
                            }
                        }
                    }
                }
            } else {
                $title = trim((string) ($_POST['title'] ?? ''));
                $slug = trim((string) ($_POST['slug'] ?: slugify($title)));
                $description = trim((string) ($_POST['description'] ?? ''));
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);
                $catId = (int) ($_POST['id'] ?? 0);
                if ($catId) {
                    CategoryModel::update($pdo, $catId, $title, $slug, $description, $sortOrder);
                } else {
                    [, $defaultLocale] = LocaleModel::active($pdo);
                    $locale = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['locale'] ?? ''))) ?: $defaultLocale;
                    CategoryModel::create($pdo, $title, $slug, $description, $sortOrder, $locale);
                }
            }
            redirect('/admin/categories');
        }
        view_admin('categories', [
            'title' => 'Категорії публікацій',
            'categories' => CategoryModel::all($pdo),
            'all_locales' => jura_available_locales($pdo),
        ]);
        exit;
    }
}
