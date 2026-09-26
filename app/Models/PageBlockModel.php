<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_page_blocks -- see App\Core\BlockRegistry for what a
 * block type actually is. */
final class PageBlockModel
{
    public static function add(PDO $pdo, int $pageId, string $type): void
    {
        $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM ' . jura_table('page_blocks') . ' WHERE page_id=' . $pageId)->fetchColumn();
        $pdo->prepare('INSERT INTO ' . jura_table('page_blocks') . ' (page_id,block_type,settings_json,sort_order) VALUES (?,?,?,?)')
            ->execute([$pageId, $type, '{}', $maxOrder + 1]);
    }

    public static function find(PDO $pdo, int $blockId, int $pageId): ?array
    {
        $block = $pdo->prepare('SELECT * FROM ' . jura_table('page_blocks') . ' WHERE id=? AND page_id=?');
        $block->execute([$blockId, $pageId]);
        return $block->fetch() ?: null;
    }

    public static function updateSettings(PDO $pdo, int $blockId, array $settings): void
    {
        $pdo->prepare('UPDATE ' . jura_table('page_blocks') . ' SET settings_json=? WHERE id=?')
            ->execute([json_encode($settings, JSON_UNESCAPED_UNICODE), $blockId]);
    }

    public static function delete(PDO $pdo, int $blockId, int $pageId): void
    {
        $pdo->prepare('DELETE FROM ' . jura_table('page_blocks') . ' WHERE id=? AND page_id=?')->execute([$blockId, $pageId]);
    }

    public static function reorder(PDO $pdo, int $pageId, array $ids): void
    {
        foreach ($ids as $i => $id) {
            $pdo->prepare('UPDATE ' . jura_table('page_blocks') . ' SET sort_order=? WHERE id=? AND page_id=?')->execute([$i + 1, (int) $id, $pageId]);
        }
    }
}
