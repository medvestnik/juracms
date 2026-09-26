<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes the jura_settings key-value table. */
final class SettingModel
{
    public static function get(PDO $pdo, string $key, mixed $default = null): mixed
    {
        $stmt = $pdo->prepare('SELECT setting_value FROM ' . jura_table('settings') . ' WHERE setting_key=? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    }

    public static function set(PDO $pdo, string $key, mixed $value, string $group = 'system', string $type = 'string'): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),setting_type=VALUES(setting_type),group_name=VALUES(group_name)')
            ->execute([$key, (string) $value, $type, $group]);
    }

    public static function all(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT setting_key,setting_value FROM ' . jura_table('settings'))->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        return $settings;
    }
}
