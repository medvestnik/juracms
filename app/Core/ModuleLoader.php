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

    /**
     * Unpacks an uploaded module .zip into modules/ so it shows up in the
     * available-modules list, ready for the normal "Встановити" button.
     * Accepts either a zip with module.json at its root, or (the common
     * convention, matching how a module directory is normally shared) a
     * single top-level folder containing module.json — the extracted
     * folder's own name is used as-is for modules/<name>, so the module's
     * actual directory casing (e.g. "GitDeploy") is preserved exactly
     * rather than re-derived from the slug.
     */
    public static function installFromZip(string $zipPath): array
    {
        $result = ['ok' => false, 'message' => '', 'slug' => null];

        if (!class_exists(\ZipArchive::class)) {
            $result['message'] = 'Розширення PHP ZipArchive недоступне на цьому хостингу — завантаження модулів архівом неможливе.';
            return $result;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $result['message'] = 'Не вдалося відкрити ZIP-архів.';
            return $result;
        }

        $tmpDir = sys_get_temp_dir() . '/jura-module-upload-' . bin2hex(random_bytes(4));
        if (!@mkdir($tmpDir, 0775, true)) {
            $zip->close();
            $result['message'] = 'Не вдалося створити тимчасову директорію.';
            return $result;
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $moduleDir = null;
        $folderName = null;
        if (is_file($tmpDir . '/module.json')) {
            $moduleDir = $tmpDir;
        } else {
            $entries = array_values(array_diff(scandir($tmpDir) ?: [], ['.', '..']));
            if (count($entries) === 1 && is_dir($tmpDir . '/' . $entries[0]) && is_file($tmpDir . '/' . $entries[0] . '/module.json')) {
                $moduleDir = $tmpDir . '/' . $entries[0];
                $folderName = $entries[0];
            }
        }

        if ($moduleDir === null) {
            self::rrmdir($tmpDir);
            $result['message'] = 'Архів не схожий на модуль JuraCMS (немає module.json у корені або в єдиній кореневій папці).';
            return $result;
        }

        $manifest = json_decode((string) file_get_contents($moduleDir . '/module.json'), true);
        if (!is_array($manifest) || empty($manifest['slug'])) {
            self::rrmdir($tmpDir);
            $result['message'] = 'module.json пошкоджений або без поля "slug".';
            return $result;
        }

        $folderName = $folderName ?? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $manifest['slug']);
        if ($folderName === '' || str_contains($folderName, '..')) {
            self::rrmdir($tmpDir);
            $result['message'] = 'Некоректна назва директорії модуля в архіві.';
            return $result;
        }

        $target = dirname(__DIR__, 2) . '/modules/' . $folderName;
        self::rrmdir($target);
        self::copyRecursive($moduleDir, $target);
        self::rrmdir($tmpDir);

        $result['ok'] = true;
        $result['slug'] = (string) $manifest['slug'];
        $result['message'] = 'Модуль «' . (string) ($manifest['name'] ?? $manifest['slug']) . '» завантажено в modules/' . $folderName . '. Тепер натисніть «Встановити» нижче.';
        return $result;
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    private static function copyRecursive(string $from, string $to): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $baseLen = strlen($from) + 1;
        foreach ($iterator as $item) {
            $target = $to . '/' . substr($item->getPathname(), $baseLen);
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    @mkdir($target, 0775, true);
                }
                continue;
            }
            $targetDir = dirname($target);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }
            @copy($item->getPathname(), $target);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    /**
     * Maps a module's slug (from its own module.json, e.g. "gitdeploy") to
     * its actual on-disk directory name (e.g. "GitDeploy"). Built by
     * scanning modules/*\/module.json rather than guessing the directory
     * name from the slug string — a slug like "gitdeploy" has no hyphen to
     * split on, so a naive ucfirst() only ever produces "Gitdeploy", never
     * "GitDeploy". That mismatch is silent and harmless on a case-insensitive
     * filesystem (macOS/Windows dev machines) but means modulePath() points
     * at a directory that doesn't exist on any real (case-sensitive) Linux
     * host — install()/readManifest() then just no-op with no error at all.
     */
    private static function slugToDirMap(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }
        $map = [];
        foreach (glob(dirname(__DIR__, 2) . '/modules/*/module.json') ?: [] as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data) && !empty($data['slug'])) {
                $map[$data['slug']] = basename(dirname($file));
            }
        }
        return $map;
    }

    private static function modulePath(string $slug): string
    {
        $dir = self::slugToDirMap()[$slug] ?? self::slugToDir($slug);
        return dirname(__DIR__, 2) . '/modules/' . $dir;
    }

    private static function slugToDir(string $slug): string
    {
        // Fallback for a slug with no matching module.json on disk yet
        // (e.g. install() called before the module's files were placed).
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
