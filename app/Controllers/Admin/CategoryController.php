<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\CategoryModel;
use PDO;

final class CategoryController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            if (($_POST['action'] ?? '') === 'delete') {
                CategoryModel::delete($pdo, (int) $_POST['id']);
            } else {
                $title = trim((string) ($_POST['title'] ?? ''));
                $slug = trim((string) ($_POST['slug'] ?: slugify($title)));
                $description = trim((string) ($_POST['description'] ?? ''));
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);
                $catId = (int) ($_POST['id'] ?? 0);
                if ($catId) {
                    CategoryModel::update($pdo, $catId, $title, $slug, $description, $sortOrder);
                } else {
                    CategoryModel::create($pdo, $title, $slug, $description, $sortOrder);
                }
            }
            redirect('/admin/categories');
        }
        view_admin('categories', ['title' => 'Категорії публікацій', 'categories' => CategoryModel::all($pdo)]);
        exit;
    }
}
