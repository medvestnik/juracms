<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_locales, and keeps the default_locale/active_locales settings in sync. */
final class LocaleModel
{
    /** [active codes ordered for display, default code]. jura_locales is the
     * source of truth; falls back to the default_locale/active_locales
     * settings only if that table is empty. */
    public static function active(PDO $pdo): array
    {
        try {
            $rows = $pdo->query('SELECT code,is_default FROM ' . jura_table('locales') . ' WHERE is_active=1 ORDER BY sort_order,id')->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }
        if ($rows) {
            $codes = array_column($rows, 'code');
            $defaultRow = current(array_filter($rows, fn($r) => (int) $r['is_default'] === 1));
            $default = $defaultRow ? (string) $defaultRow['code'] : (string) $codes[0];
            return [$codes, $default];
        }
        $settings = SettingModel::all($pdo);
        $default = (string) ($settings['default_locale'] ?? 'uk');
        $active = array_values(array_filter(array_map('trim', explode(',', (string) ($settings['active_locales'] ?? $default)))));
        return [$active ?: [$default], $default];
    }

    /** Re-saves the active_locales setting from jura_locales -- call after any
     * locale create/update/setDefault/delete so the fallback stays in sync. */
    public static function syncActiveSetting(PDO $pdo): void
    {
        [$activeCodes] = self::active($pdo);
        SettingModel::set($pdo, 'active_locales', implode(',', $activeCodes), 'localization');
    }

    public static function create(PDO $pdo, string $code, string $name, string $native, int $sortOrder): void
    {
        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('locales') . ' (code,name,native_name,sort_order) VALUES (?,?,?,?)')
            ->execute([$code, $name, $native, $sortOrder]);
    }

    public static function update(PDO $pdo, int $id, string $name, string $native, int $sortOrder, bool $isActive): void
    {
        $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET name=?,native_name=?,sort_order=?,is_active=? WHERE id=?')
            ->execute([$name, $native, $sortOrder, $isActive ? 1 : 0, $id]);
    }

    public static function setDefault(PDO $pdo, int $id): void
    {
        $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET is_default=0')->execute();
        $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET is_default=1,is_active=1 WHERE id=?')->execute([$id]);
        $code = $pdo->prepare('SELECT code FROM ' . jura_table('locales') . ' WHERE id=?');
        $code->execute([$id]);
        if ($c = $code->fetchColumn()) {
            SettingModel::set($pdo, 'default_locale', (string) $c, 'localization');
        }
    }

    public static function isDefault(PDO $pdo, int $id): bool
    {
        $row = $pdo->prepare('SELECT is_default FROM ' . jura_table('locales') . ' WHERE id=?');
        $row->execute([$id]);
        return (int) $row->fetchColumn() === 1;
    }

    /** Deletes the locale unless it's the default one; returns whether it deleted it. */
    public static function delete(PDO $pdo, int $id): bool
    {
        if (self::isDefault($pdo, $id)) {
            return false;
        }
        $pdo->prepare('DELETE FROM ' . jura_table('locales') . ' WHERE id=?')->execute([$id]);
        return true;
    }
}
