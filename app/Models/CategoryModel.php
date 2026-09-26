<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_post_categories (and its post relation table for deletes). */
final class CategoryModel
{
    public static function all(PDO $pdo): array
    {
        return $pdo->query('SELECT c.*,(SELECT COUNT(*) FROM ' . jura_table('post_category_relations') . ' r WHERE r.category_id=c.id) post_count FROM ' . jura_table('post_categories') . ' c ORDER BY sort_order,title')->fetchAll();
    }

    /** id,title for populating a post's category select. */
    public static function options(PDO $pdo): array
    {
        return $pdo->query('SELECT id,title FROM ' . jura_table('post_categories') . ' ORDER BY sort_order,title')->fetchAll();
    }

    public static function create(PDO $pdo, string $title, string $slug, string $description, int $sortOrder): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('post_categories') . ' (title,slug,description,sort_order) VALUES (?,?,?,?)')
            ->execute([$title, $slug, $description, $sortOrder]);
    }

    public static function update(PDO $pdo, int $id, string $title, string $slug, string $description, int $sortOrder): void
    {
        $pdo->prepare('UPDATE ' . jura_table('post_categories') . ' SET title=?,slug=?,description=?,sort_order=?,updated_at=NOW() WHERE id=?')
            ->execute([$title, $slug, $description, $sortOrder, $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('post_category_relations') . ' WHERE category_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('post_categories') . ' WHERE id=?')->execute([$id]);
    }
}
