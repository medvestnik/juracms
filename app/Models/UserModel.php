<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_users. */
final class UserModel
{
    public static function all(PDO $pdo): array
    {
        return $pdo->query('SELECT id,email,status,role,created_at FROM ' . jura_table('users') . ' ORDER BY id')->fetchAll();
    }

    /** id, email, password_hash, status for the login check -- null if no such email. */
    public static function findForLogin(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('SELECT id,email,password_hash,status FROM ' . jura_table('users') . ' WHERE email=:email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(PDO $pdo, string $email, string $passwordHash, string $role): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('users') . ' (email,password_hash,status,role) VALUES (?,?,?,?)')
            ->execute([$email, $passwordHash, 'active', $role]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('users') . ' WHERE id=?')->execute([$id]);
    }

    public static function updatePassword(PDO $pdo, int $id, string $passwordHash): void
    {
        $pdo->prepare('UPDATE ' . jura_table('users') . ' SET password_hash=? WHERE id=?')->execute([$passwordHash, $id]);
    }

    public static function toggleStatus(PDO $pdo, int $id): void
    {
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('users') . ' WHERE id=?');
        $row->execute([$id]);
        $cur = (string) ($row->fetchColumn() ?: 'active');
        $next = $cur === 'active' ? 'inactive' : 'active';
        $pdo->prepare('UPDATE ' . jura_table('users') . ' SET status=? WHERE id=?')->execute([$next, $id]);
    }
}
