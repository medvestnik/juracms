<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Registry of page-builder block types -- the engine behind "Блоки сторінки"
 * in the Pages editor. A block type is a bundle of three callables:
 *
 *   - admin_form(array $settings): string   -- renders the settings inputs
 *     shown inside this block's card in the admin editor.
 *   - save_settings(array $post): array     -- turns that form's $_POST into
 *     a clean settings array to store as JSON.
 *   - render(array $settings, \PDO $pdo): string -- renders the block on the
 *     frontend.
 *
 * Core block types are registered once from core/Blocks/CoreBlocks.php;
 * modules can register their own the same way ModuleLoader hooks work,
 * so e.g. Hotel could later offer a "booking form" block.
 */
final class BlockRegistry
{
    private static array $types = [];

    public static function register(string $type, array $config): void
    {
        self::$types[$type] = $config;
    }

    public static function has(string $type): bool
    {
        return isset(self::$types[$type]);
    }

    /** type => label, in registration order -- for the "+ Додати блок" picker. */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::$types as $type => $config) {
            $labels[$type] = (string) ($config['label'] ?? $type);
        }
        return $labels;
    }

    public static function label(string $type): string
    {
        return (string) (self::$types[$type]['label'] ?? $type);
    }

    public static function adminForm(string $type, array $settings): string
    {
        $fn = self::$types[$type]['admin_form'] ?? null;
        if (!is_callable($fn)) {
            return '<p style="color:#94a3b8">Невідомий тип блоку «' . \e($type) . '».</p>';
        }
        try {
            return (string) $fn($settings);
        } catch (\Throwable $e) {
            return '<p style="color:#9f1239">Помилка форми блоку: ' . \e($e->getMessage()) . '</p>';
        }
    }

    /** $current is the block's existing settings before this save -- lets a
     * block type (e.g. "image") preserve a field managed by a separate
     * route (an /upload-image sub-form) that this settings form never
     * submits, instead of it being wiped back to empty on every save. */
    public static function saveSettings(string $type, array $post, array $current = []): array
    {
        $fn = self::$types[$type]['save_settings'] ?? null;
        if (!is_callable($fn)) {
            return [];
        }
        try {
            return (array) $fn($post, $current);
        } catch (\Throwable) {
            return [];
        }
    }

    /** Renders one block; a failure here must not take the whole page down
     * with it -- same isolation principle as ModuleLoader::loadInstalled(). */
    public static function render(string $type, array $settings, \PDO $pdo): string
    {
        $fn = self::$types[$type]['render'] ?? null;
        if (!is_callable($fn)) {
            return '';
        }
        try {
            return (string) $fn($settings, $pdo);
        } catch (\Throwable $e) {
            error_log("BlockRegistry: block '{$type}' failed to render: " . $e->getMessage());
            return '';
        }
    }
}
