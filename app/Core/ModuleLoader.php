<?php

declare(strict_types=1);

namespace App\Core;

final class ModuleLoader
{
    private static array $registry = [];

    // ── Schema ─────────────────────────────────────────────────────────────
    public static function ensureTable(\PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . \jura_table('modules') . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(80) UNIQUE NOT NULL,
            name VARCHAR(191) NOT NULL,
            version VARCHAR(40) NOT NULL DEFAULT '1.0.0',
            installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // ── Registry ────────────────────────────────────────────────────────────
    public static function register(string $slug, array $config): void
    {
        self::$registry[$slug] = $config;
    }

    public static function getRegistry(): array
    {
        return self::$registry;
    }

    // ── Load installed modules ──────────────────────────────────────────────
    public static function loadInstalled(\PDO $pdo): void
    {
        try {
            $slugs = $pdo->query('SELECT slug FROM ' . \jura_table('modules'))->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable) {
            return;
        }
        foreach ($slugs as $slug) {
            $bootstrap = self::modulePath($slug) . '/bootstrap.php';
            if (is_file($bootstrap)) {
                require_once $bootstrap;
            }
        }
    }

    // ── Auto-migrate: register modules that were installed before module system ──
    public static function autoMigrate(\PDO $pdo): void
    {
        foreach (self::availableManifests() as $manifest) {
            $slug = $manifest['slug'];
            $detectTable = $manifest['detect_table'] ?? null;
            if (!$detectTable) continue;

            // If the module's table exists but it's not registered in jura_modules → install it
            $tableName = \jura_table($detectTable);
            try {
                $exists = (bool) $pdo->query("SHOW TABLES LIKE " . $pdo->quote(str_replace('`', '', $tableName)))->fetch();
            } catch (\Throwable) {
                $exists = false;
            }
            if ($exists) {
                $pdo->prepare('INSERT IGNORE INTO ' . \jura_table('modules') . ' (slug,name,version) VALUES (?,?,?)')
                    ->execute([$slug, $manifest['name'], $manifest['version'] ?? '1.0.0']);
            }
        }
    }

    // ── Hooks ───────────────────────────────────────────────────────────────
    /** Call a hook on every registered module (fire-and-forget). */
    public static function hookEach(string $hook, mixed ...$args): void
    {
        foreach (self::$registry as $config) {
            if (!empty($config[$hook]) && is_callable($config[$hook])) {
                ($config[$hook])(...$args);
            }
        }
    }

    /** Call a hook on modules until one returns true. */
    public static function hookFirst(string $hook, mixed ...$args): bool
    {
        foreach (self::$registry as $config) {
            if (!empty($config[$hook]) && is_callable($config[$hook])) {
                if (($config[$hook])(...$args)) return true;
            }
        }
        return false;
    }

    // ── Admin nav ───────────────────────────────────────────────────────────
    /** Returns nav groups contributed by installed modules. */
    public static function getAdminNav(): array
    {
        $nav = [];
        foreach (self::$registry as $config) {
            if (!empty($config['admin_nav_group']) && !empty($config['admin_nav'])) {
                $nav[$config['admin_nav_group']] = $config['admin_nav'];
            }
        }
        return $nav;
    }

    // ── Module management ────────────────────────────────────────────────────
    public static function available(\PDO $pdo): array
    {
        try {
            $installed = $pdo->query('SELECT slug FROM ' . \jura_table('modules'))->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable) {
            $installed = [];
        }
        $modules = [];
        foreach (self::availableManifests() as $manifest) {
            $manifest['installed'] = in_array($manifest['slug'], $installed, true);
            $modules[] = $manifest;
        }
        return $modules;
    }

    public static function install(string $slug, \PDO $pdo): void
    {
        $manifest = self::readManifest($slug);
        if (!$manifest) return;

        // Include bootstrap so hooks are available
        $bootstrap = self::modulePath($slug) . '/bootstrap.php';
        if (is_file($bootstrap)) require_once $bootstrap;

        // Call install hook (usually ensure_schema)
        if (!empty(self::$registry[$slug]['install']) && is_callable(self::$registry[$slug]['install'])) {
            (self::$registry[$slug]['install'])($pdo);
        }

        $pdo->prepare('INSERT IGNORE INTO ' . \jura_table('modules') . ' (slug,name,version) VALUES (?,?,?)')
            ->execute([$slug, $manifest['name'], $manifest['version'] ?? '1.0.0']);
    }

    public static function uninstall(string $slug, \PDO $pdo): void
    {
        // Call uninstall hook if defined
        if (!empty(self::$registry[$slug]['uninstall']) && is_callable(self::$registry[$slug]['uninstall'])) {
            (self::$registry[$slug]['uninstall'])($pdo);
        }
        $pdo->prepare('DELETE FROM ' . \jura_table('modules') . ' WHERE slug=?')->execute([$slug]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private static function modulePath(string $slug): string
    {
        return dirname(__DIR__, 2) . '/modules/' . self::slugToDir($slug);
    }

    private static function slugToDir(string $slug): string
    {
        // e.g. "hotel" → "Hotel", "real-estate" → "RealEstate"
        return implode('', array_map('ucfirst', explode('-', $slug)));
    }

    private static function readManifest(string $slug): ?array
    {
        $file = self::modulePath($slug) . '/module.json';
        if (!is_file($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        return (is_array($data) && !empty($data['slug'])) ? $data : null;
    }

    private static function availableManifests(): array
    {
        $manifests = [];
        foreach (glob(dirname(__DIR__, 2) . '/modules/*/module.json') ?: [] as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data) && !empty($data['slug'])) {
                $manifests[] = $data;
            }
        }
        return $manifests;
    }
}
