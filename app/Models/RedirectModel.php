<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_redirects. */
final class RedirectModel
{
    /** Active redirect for an exact source path, or null. Bumps its hit
     * counter as a side effect -- this is the frontend lookup, called once
     * per matching request. */
    public static function findActive(PDO $pdo, string $path): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('redirects') . ' WHERE source_path=? AND is_active=1 LIMIT 1');
        $stmt->execute([$path]);
        $redirect = $stmt->fetch();
        if (!$redirect) {
            return null;
        }
        $pdo->prepare('UPDATE ' . jura_table('redirects') . ' SET hit_count=COALESCE(hit_count,0)+1,last_hit_at=NOW() WHERE id=?')->execute([(int) $redirect['id']]);
        return $redirect;
    }

    public static function all(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM ' . jura_table('redirects') . ' ORDER BY id DESC')->fetchAll();
    }

    public static function upsert(PDO $pdo, string $source, string $target, int $statusCode, bool $isActive, string $notes): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('redirects') . ' (source_path,target_path,status_code,is_active,notes) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE target_path=VALUES(target_path),status_code=VALUES(status_code),is_active=VALUES(is_active),notes=VALUES(notes)')
            ->execute([$source, $target, $statusCode, $isActive ? 1 : 0, $notes]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('redirects') . ' WHERE id=?')->execute([$id]);
    }
}
