<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Resolves and writes jura_routes -- the path -> (page|post) lookup table
 * that drives both locale-prefixed URLs and the frontend dispatcher. */
final class RouteModel
{
    /** [locale, path without the locale prefix] for a request path -- e.g.
     * /en/about -> ['en', '/about'] when 'en' is an active locale, or
     * [$default, $path] unchanged otherwise. */
    public static function currentLocale(PDO $pdo, string $path): array
    {
        [$active, $default] = LocaleModel::active($pdo);
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $prefix = $segments[0] ?? '';
        if (in_array($prefix, $active, true)) {
            array_shift($segments);
            return [$prefix, ensure_path(implode('/', $segments))];
        }
        return [$default, $path];
    }

    public static function find(PDO $pdo, string $path): ?array
    {
        [$locale, $routePath] = self::currentLocale($pdo, $path);
        $table = jura_table('routes');
        foreach ([$path, $routePath] as $candidate) {
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE path=:p AND status='active' LIMIT 1");
            $stmt->execute(['p' => $candidate]);
            $route = $stmt->fetch();
            if ($route) {
                $route['locale'] = $locale;
                return $route;
            }
        }
        return null;
    }

    public static function save(PDO $pdo, string $path, string $entityType, int $entityId): void
    {
        $path = ensure_path($path);
        $pdo->prepare('INSERT INTO ' . jura_table('routes') . ' (path,entity_type,entity_id,status) VALUES (?,?,?,"active") ON DUPLICATE KEY UPDATE entity_type=VALUES(entity_type),entity_id=VALUES(entity_id),status="active"')
            ->execute([$path, $entityType, $entityId]);
    }

    public static function deleteFor(PDO $pdo, string $entityType, int $entityId): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('routes') . ' WHERE entity_type=? AND entity_id=?')->execute([$entityType, $entityId]);
    }

    public static function hasFor(PDO $pdo, string $entityType, int $entityId): bool
    {
        $stmt = $pdo->prepare('SELECT id FROM ' . jura_table('routes') . ' WHERE entity_type=? AND entity_id=? LIMIT 1');
        $stmt->execute([$entityType, $entityId]);
        return (bool) $stmt->fetch();
    }

    /**
     * All translations of a page/post, keyed by locale code -- including the
     * row itself. $table is 'pages' or 'posts'; $row must have id, locale,
     * translation_of.
     */
    public static function entityTranslations(PDO $pdo, string $table, array $row): array
    {
        $rootId = self::translationRootId($row);
        $entityType = rtrim($table, 's'); // 'pages' -> 'page', 'posts' -> 'post'
        $stmt = $pdo->prepare(
            'SELECT e.id,e.locale,e.title,r.path route_path FROM ' . jura_table($table) . ' e '
            . 'LEFT JOIN ' . jura_table('routes') . " r ON r.entity_type=? AND r.entity_id=e.id "
            . 'WHERE e.id=? OR e.translation_of=?'
        );
        $stmt->execute([$entityType, $rootId, $rootId]);
        $out = [];
        foreach ($stmt->fetchAll() as $t) {
            $out[(string) $t['locale']] = $t;
        }
        return $out;
    }

    /** The default-locale-most page/post id a translation belongs to (itself if it has no translation_of). */
    public static function translationRootId(array $row): int
    {
        return (int) ($row['translation_of'] ?: $row['id']);
    }
}
