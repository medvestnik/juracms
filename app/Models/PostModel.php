<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_posts and its category relation table. */
final class PostModel
{
    public static function count(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table('posts'))->fetchColumn();
    }

    public static function paginate(PDO $pdo, string $orderCol, string $dir, int $perPage, int $offset): array
    {
        $query = "SELECT p.*,c.title category_title FROM " . jura_table('posts') . " p LEFT JOIN " . jura_table('post_category_relations') . " r ON r.post_id=p.id LEFT JOIN " . jura_table('post_categories') . " c ON c.id=r.category_id ORDER BY {$orderCol} {$dir}, p.id DESC LIMIT {$perPage} OFFSET {$offset}";
        return $pdo->query($query)->fetchAll();
    }

    public static function reorder(PDO $pdo, array $ids): void
    {
        foreach ($ids as $i => $id) {
            $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET sort_order=? WHERE id=?')->execute([$i + 1, (int) $id]);
        }
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . ' WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findPublished(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . ' WHERE id=? AND status="published" LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Frontend blog listing ────────────────────────────────────────────

    /** Posts inserted directly (old migrations, manual SQL) can predate the
     * locale column and sit at '' — treat those as belonging to every locale
     * rather than vanishing from the blog until someone explicitly assigns
     * them one. */
    public static function countPublishedForLocale(PDO $pdo, string $locale): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . jura_table('posts') . " WHERE status='published' AND (locale=? OR locale='')");
        $stmt->execute([$locale]);
        return (int) $stmt->fetchColumn();
    }

    public static function paginatePublishedForLocale(PDO $pdo, string $locale, int $perPage, int $offset): array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . " WHERE status='published' AND (locale=? OR locale='') ORDER BY published_at DESC, id DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute([$locale]);
        return $stmt->fetchAll();
    }

    public static function create(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('INSERT INTO ' . jura_table('posts') . ' (slug,title,excerpt,content,status,meta_title,meta_description,published_at,locale) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['slug'], $data['title'], $data['excerpt'], $data['content'], $data['status'],
            $data['meta_title'], $data['meta_description'], $data['published_at'], $data['locale'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET slug=?,title=?,excerpt=?,content=?,status=?,meta_title=?,meta_description=?,published_at=?,featured_image=?,updated_at=NOW() WHERE id=?')
            ->execute([
                $data['slug'], $data['title'], $data['excerpt'], $data['content'], $data['status'],
                $data['meta_title'], $data['meta_description'], $data['published_at'], $data['featured_image'], $id,
            ]);
    }

    public static function updateFeaturedImage(PDO $pdo, int $id, ?string $filename): void
    {
        $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET featured_image=? WHERE id=?')->execute([$filename, $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('posts') . ' WHERE id=?')->execute([$id]);
    }

    public static function toggleStatus(PDO $pdo, int $id): void
    {
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('posts') . ' WHERE id=?');
        $row->execute([$id]);
        $cur = (string) ($row->fetchColumn() ?: 'draft');
        $next = $cur === 'published' ? 'draft' : 'published';
        $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET status=?,updated_at=NOW() WHERE id=?')->execute([$next, $id]);
    }

    public static function findTranslationId(PDO $pdo, int $rootId, string $locale): int
    {
        $existing = $pdo->prepare('SELECT id FROM ' . jura_table('posts') . ' WHERE (id=? OR translation_of=?) AND locale=?');
        $existing->execute([$rootId, $rootId, $locale]);
        return (int) $existing->fetchColumn();
    }

    public static function createTranslation(PDO $pdo, array $source, string $locale, int $rootId): int
    {
        $ins = $pdo->prepare('INSERT INTO ' . jura_table('posts') . ' (slug,title,excerpt,content,status,meta_title,meta_description,locale,translation_of) VALUES (?,?,?,?,?,?,?,?,?)');
        $ins->execute([$source['slug'], $source['title'], $source['excerpt'], '', 'draft', '', '', $locale, $rootId]);
        return (int) $pdo->lastInsertId();
    }

    // ── Category relation ───────────────────────────────────────────────

    public static function categoryIdFor(PDO $pdo, int $postId): int
    {
        $catStmt = $pdo->prepare('SELECT category_id FROM ' . jura_table('post_category_relations') . ' WHERE post_id=? LIMIT 1');
        $catStmt->execute([$postId]);
        return (int) $catStmt->fetchColumn();
    }

    public static function setCategory(PDO $pdo, int $postId, int $categoryId): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('post_category_relations') . ' WHERE post_id=?')->execute([$postId]);
        if ($categoryId) {
            $pdo->prepare('INSERT INTO ' . jura_table('post_category_relations') . ' (post_id,category_id) VALUES (?,?)')->execute([$postId, $categoryId]);
        }
    }

    /** For a brand-new post: no prior relation to delete. */
    public static function addCategory(PDO $pdo, int $postId, int $categoryId): void
    {
        if ($categoryId) {
            $pdo->prepare('INSERT INTO ' . jura_table('post_category_relations') . ' (post_id,category_id) VALUES (?,?)')->execute([$postId, $categoryId]);
        }
    }
}
