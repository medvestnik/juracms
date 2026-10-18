<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_post_categories (and its post relation table for deletes). */
final class CategoryModel
{
    /** Every category row (every locale), grouped with translations of the
     * same root next to each other for the admin list. */
    public static function all(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT c.*,(SELECT COUNT(*) FROM ' . jura_table('post_category_relations') . ' r WHERE r.category_id=c.id) post_count '
            . 'FROM ' . jura_table('post_categories') . ' c '
            . 'ORDER BY COALESCE(c.translation_of,c.id),(c.translation_of IS NOT NULL),c.sort_order,c.title'
        )->fetchAll();
    }

    /** id,title for populating a post's category select, restricted to the
     * post's own locale (plus pre-locale legacy rows, same fallback as
     * posts/pages/menu_items) so e.g. an English post can't be filed under a
     * Russian-only category. */
    public static function options(PDO $pdo, string $locale = ''): array
    {
        if ($locale === '') {
            return $pdo->query('SELECT id,title FROM ' . jura_table('post_categories') . ' ORDER BY sort_order,title')->fetchAll();
        }
        $stmt = $pdo->prepare("SELECT id,title FROM " . jura_table('post_categories') . " WHERE locale=? OR locale='' ORDER BY sort_order,title");
        $stmt->execute([$locale]);
        return $stmt->fetchAll();
    }

    public static function create(PDO $pdo, string $title, string $slug, string $description, int $sortOrder, string $locale = ''): int
    {
        $pdo->prepare('INSERT INTO ' . jura_table('post_categories') . ' (title,slug,description,sort_order,locale) VALUES (?,?,?,?,?)')
            ->execute([$title, $slug, $description, $sortOrder, $locale]);
        return (int) $pdo->lastInsertId();
    }

    public static function findTranslationId(PDO $pdo, int $rootId, string $locale): int
    {
        $stmt = $pdo->prepare('SELECT id FROM ' . jura_table('post_categories') . ' WHERE (id=? OR translation_of=?) AND locale=?');
        $stmt->execute([$rootId, $rootId, $locale]);
        return (int) $stmt->fetchColumn();
    }

    public static function createTranslation(PDO $pdo, array $source, string $locale, int $rootId): int
    {
        $pdo->prepare('INSERT INTO ' . jura_table('post_categories') . ' (title,slug,description,sort_order,locale,translation_of) VALUES (?,?,?,?,?,?)')
            ->execute([$source['title'], $source['slug'], $source['description'] ?? '', $source['sort_order'] ?? 0, $locale, $rootId]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, string $title, string $slug, string $description, int $sortOrder): void
    {
        $pdo->prepare('UPDATE ' . jura_table('post_categories') . ' SET title=?,slug=?,description=?,sort_order=?,updated_at=NOW() WHERE id=?')
            ->execute([$title, $slug, $description, $sortOrder, $id]);
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('post_categories') . ' WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('post_category_relations') . ' WHERE category_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('post_categories') . ' WHERE id=?')->execute([$id]);
    }
}
