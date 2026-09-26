<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_pages. */
final class PageModel
{
    public static function count(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table('pages'))->fetchColumn();
    }

    public static function paginate(PDO $pdo, string $orderCol, string $dir, int $perPage, int $offset): array
    {
        $query = 'SELECT p.*,r.path route_path FROM ' . jura_table('pages') . ' p LEFT JOIN ' . jura_table('routes') . " r ON r.entity_type='page' AND r.entity_id=p.id ORDER BY {$orderCol} {$dir}, p.id DESC LIMIT {$perPage} OFFSET {$offset}";
        return $pdo->query($query)->fetchAll();
    }

    public static function reorder(PDO $pdo, array $ids): void
    {
        foreach ($ids as $i => $id) {
            $pdo->prepare('UPDATE ' . jura_table('pages') . ' SET sort_order=? WHERE id=?')->execute([$i + 1, (int) $id]);
        }
    }

    /** With the joined route path, for the admin edit form. */
    public static function findWithRoute(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT p.*,r.path route_path FROM ' . jura_table('pages') . ' p LEFT JOIN ' . jura_table('routes') . " r ON r.entity_type='page' AND r.entity_id=p.id WHERE p.id=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('pages') . ' WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findPublished(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('pages') . ' WHERE id=? AND status="published" LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(PDO $pdo, string $slug): ?array
    {
        $stmt = $pdo->prepare('SELECT id, slug FROM ' . jura_table('pages') . ' WHERE slug=? ORDER BY id LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('INSERT INTO ' . jura_table('pages') . ' (author_id,title,slug,content,excerpt,status,template,meta_title,meta_description,meta_keywords,canonical_path,og_title,og_description,sort_order,locale,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CASE WHEN ?="published" THEN NOW() ELSE NULL END)');
        $stmt->execute([
            $data['author_id'], $data['title'], $data['slug'], $data['content'], $data['excerpt'],
            $data['status'], $data['template'], $data['meta_title'], $data['meta_description'], $data['meta_keywords'],
            $data['canonical_path'], $data['og_title'], $data['og_description'], $data['sort_order'], $data['locale'], $data['status'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        $pdo->prepare('UPDATE ' . jura_table('pages') . ' SET title=?,slug=?,content=?,excerpt=?,status=?,template=?,meta_title=?,meta_description=?,meta_keywords=?,canonical_path=?,og_title=?,og_description=?,sort_order=?,updated_at=NOW(),published_at=CASE WHEN ?="published" AND published_at IS NULL THEN NOW() ELSE published_at END WHERE id=?')
            ->execute([
                $data['title'], $data['slug'], $data['content'], $data['excerpt'], $data['status'], $data['template'],
                $data['meta_title'], $data['meta_description'], $data['meta_keywords'], $data['canonical_path'],
                $data['og_title'], $data['og_description'], $data['sort_order'], $data['status'], $id,
            ]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('pages') . ' WHERE id=?')->execute([$id]);
    }

    public static function toggleStatus(PDO $pdo, int $id): void
    {
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('pages') . ' WHERE id=?');
        $row->execute([$id]);
        $cur = (string) ($row->fetchColumn() ?: 'draft');
        $next = $cur === 'published' ? 'draft' : 'published';
        $pdo->prepare('UPDATE ' . jura_table('pages') . ' SET status=?,updated_at=NOW() WHERE id=?')->execute([$next, $id]);
    }

    /** Existing translation id for $rootId in $locale, or 0 if none. */
    public static function findTranslationId(PDO $pdo, int $rootId, string $locale): int
    {
        $existing = $pdo->prepare('SELECT id FROM ' . jura_table('pages') . ' WHERE (id=? OR translation_of=?) AND locale=?');
        $existing->execute([$rootId, $rootId, $locale]);
        return (int) $existing->fetchColumn();
    }

    public static function createTranslation(PDO $pdo, array $source, string $locale, int $rootId, int $authorId): int
    {
        $ins = $pdo->prepare('INSERT INTO ' . jura_table('pages') . ' (author_id,title,slug,content,excerpt,status,template,meta_title,meta_description,meta_keywords,sort_order,locale,translation_of) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([$authorId, $source['title'], $source['slug'], '', $source['excerpt'], 'draft', $source['template'], '', '', '', $source['sort_order'], $locale, $rootId]);
        return (int) $pdo->lastInsertId();
    }

    // ── Maintenance helpers (bulk/admin-tools operations) ──────────────────

    public static function all(PDO $pdo): array
    {
        return $pdo->query('SELECT id, slug FROM ' . jura_table('pages') . ' ORDER BY id')->fetchAll();
    }

    public static function setTemplateBySlug(PDO $pdo, string $slug, string $template): void
    {
        $pdo->prepare('UPDATE ' . jura_table('pages') . ' SET template=? WHERE slug=?')->execute([$template, $slug]);
    }

    public static function setContentBySlug(PDO $pdo, string $slug, string $content): void
    {
        $pdo->prepare('UPDATE ' . jura_table('pages') . " SET content=? WHERE slug=? ORDER BY id LIMIT 1")->execute([$content, $slug]);
    }
}
