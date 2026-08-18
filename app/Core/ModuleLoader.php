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
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Older installs created this table before the `enabled` column
        // existed — add it the same idempotent, concurrency-safe way the
        // rest of the schema is patched (see ensure_cms_schema()).
        $tableName = str_replace('`', '', \jura_table('modules'));
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'enabled'");
        if (!$stmt->fetch()) {
            try {
                $pdo->exec("ALTER TABLE `{$tableName}` ADD `enabled` TINYINT(1) NOT NULL DEFAULT 1");
            } catch (\PDOException $e) {
                if ($e->getCode() !== '42S21') {
                    throw $e;
                }
            }
        }
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
    /** Loads only modules that are both installed and enabled — a disabled
     * module stays installed (data intact) but its hooks/nav/routes never
     * fire, same effect as if it were never installed. */
    public static function loadInstalled(\PDO $pdo): void
    {
        try {
            $slugs = $pdo->query('SELECT slug FROM ' . \jura_table('modules') . ' WHERE enabled=1')->fetchAll(\PDO::FETCH_COLUMN);
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

    /** Call a hook on every module and merge their array returns into one —
     * lets a module contribute extra dashboard stats or template data
     * without core needing to know the module exists (e.g. admin_stats,
     * home_data). */
    public static function hookCollect(string $hook, mixed ...$args): array
    {
        $result = [];
        foreach (self::$registry as $config) {
            if (!empty($config[$hook]) && is_callable($config[$hook])) {
                $r = ($config[$hook])(...$args);
                if (is_array($r)) {
                    $result = array_merge($result, $r);
                }
            }
        }
        return $result;
    }

    /** Call a hook on every module and concatenate their string returns —
     * lets a module render extra markup into a fixed core slot (e.g. extra
     * dashboard widget cards). */
    public static function hookRender(string $hook, mixed ...$args): string
    {
        $out = '';
        foreach (self::$registry as $config) {
            if (!empty($config[$hook]) && is_callable($config[$hook])) {
                $r = ($config[$hook])(...$args);
                if (is_string($r)) {
                    $out .= $r;
                }
            }
        }
        return $out;
    }

    /** Pipe a value through every module's hook, each getting the previous
     * module's output — lets a module transform content generically (e.g.
     * shortcode substitution in page content) without core special-casing
     * any one module. */
    public static function hookFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach (self::$registry as $config) {
            if (!empty($config[$hook]) && is_callable($config[$hook])) {
                $value = ($config[$hook])($value, ...$args);
            }
        }
        return $value;
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
            $rows = $pdo->query('SELECT slug, enabled FROM ' . \jura_table('modules'))->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $rows = [];
        }
        $installed = [];
        foreach ($rows as $row) {
            $installed[$row['slug']] = (bool) $row['enabled'];
        }
        $modules = [];
        foreach (self::availableManifests() as $manifest) {
            $manifest['installed'] = array_key_exists($manifest['slug'], $installed);
            $manifest['enabled'] = $manifest['installed'] ? $installed[$manifest['slug']] : false;
            $modules[] = $manifest;
        }
        return $modules;
    }

    public static function toggle(string $slug, bool $enabled, \PDO $pdo): void
    {
        $pdo->prepare('UPDATE ' . \jura_table('modules') . ' SET enabled=? WHERE slug=?')
            ->execute([$enabled ? 1 : 0, $slug]);
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
