<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_menus and jura_menu_items. */
final class MenuModel
{
    /** Active items for a menu code in display order -- used by the frontend
     * layout to render header/footer nav. Never fatals if the tables are
     * (temporarily) missing. */
    public static function frontendItems(PDO $pdo, string $code, string $locale = ''): array
    {
        try {
            $stmt = $pdo->prepare('SELECT i.* FROM ' . jura_table('menu_items') . ' i JOIN ' . jura_table('menus') . " m ON m.id=i.menu_id WHERE m.code=? AND i.status='active' AND (i.locale=? OR i.locale='') ORDER BY i.sort_order,i.id");
            $stmt->execute([$code, $locale]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function allMenus(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM ' . jura_table('menus') . ' ORDER BY name')->fetchAll();
    }

    public static function allItems(PDO $pdo): array
    {
        return $pdo->query('SELECT i.*,m.name menu_name,m.code menu_code FROM ' . jura_table('menu_items') . ' i JOIN ' . jura_table('menus') . ' m ON m.id=i.menu_id ORDER BY m.name,i.sort_order,i.id')->fetchAll();
    }

    /** Published pages available to link a menu item to. */
    public static function linkablePages(PDO $pdo): array
    {
        return $pdo->query('SELECT id,title,slug,template FROM ' . jura_table('pages') . " WHERE status='published' ORDER BY title")->fetchAll();
    }

    public static function createMenu(PDO $pdo, string $code, string $name): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('menus') . ' (code,name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)')->execute([$code, $name]);
    }

    public static function renameMenu(PDO $pdo, int $id, string $name): void
    {
        $pdo->prepare('UPDATE ' . jura_table('menus') . ' SET name=? WHERE id=?')->execute([$name, $id]);
    }

    public static function deleteMenu(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('menu_items') . ' WHERE menu_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('menus') . ' WHERE id=?')->execute([$id]);
    }

    public static function addItem(PDO $pdo, int $menuId, ?int $parentId, string $title, string $url, string $target, int $sortOrder, string $status, string $locale): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('menu_items') . ' (menu_id,parent_id,title,url,target,sort_order,status,locale) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$menuId, $parentId, $title, $url, $target, $sortOrder, $status, $locale]);
    }

    public static function editItem(PDO $pdo, int $id, string $title, string $url, int $sortOrder, string $locale): void
    {
        $pdo->prepare('UPDATE ' . jura_table('menu_items') . ' SET title=?,url=?,sort_order=?,locale=? WHERE id=?')
            ->execute([$title, $url, $sortOrder, $locale, $id]);
    }

    public static function deleteItem(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('menu_items') . ' WHERE id=?')->execute([$id]);
    }

    // ── Maintenance helpers ─────────────────────────────────────────────

    public static function findIdByCode(PDO $pdo, string $code): int
    {
        $stmt = $pdo->prepare('SELECT id FROM ' . jura_table('menus') . ' WHERE code=? LIMIT 1');
        $stmt->execute([$code]);
        return (int) $stmt->fetchColumn();
    }

    public static function insertMenu(PDO $pdo, string $code, string $name): int
    {
        $pdo->prepare('INSERT INTO ' . jura_table('menus') . ' (code,name) VALUES (?,?)')->execute([$code, $name]);
        return (int) $pdo->lastInsertId();
    }

    public static function clearItems(PDO $pdo, int $menuId): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('menu_items') . ' WHERE menu_id=?')->execute([$menuId]);
    }

    public static function insertItem(PDO $pdo, int $menuId, string $title, string $url, int $sortOrder, string $status): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('menu_items') . ' (menu_id,title,url,sort_order,status) VALUES (?,?,?,?,?)')
            ->execute([$menuId, $title, $url, $sortOrder, $status]);
    }
}
