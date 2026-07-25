<?php

declare(strict_types=1);

namespace Core\Installer;

final class Runtime
{
    public static function lockFile(): string
    {
        return BASE_PATH . '/storage/installed.lock';
    }

    public static function configFile(): string
    {
        return BASE_PATH . '/config.php';
    }

    public static function config(): array
    {
        $file = self::configFile();
        if (!is_file($file)) {
            return [];
        }

        $config = require $file;
        return is_array($config) ? $config : [];
    }

    public static function isInstalled(): bool
    {
        $config = self::config();
        $installedFlag = (bool) ($config['app']['installed'] ?? false);

        if (!is_file(self::configFile()) || $installedFlag !== true) {
            return false;
        }

        if (!self::coreTablesExist($config)) {
            // config.php says installed, but the database itself doesn't have
            // the core tables (e.g. someone dropped everything to redo the
            // install from scratch). Treat that as "not installed" so the
            // user lands back on the wizard instead of a fatal error on the
            // first missing table.
            return false;
        }

        // config.php with app.installed=true is the authoritative signal.
        // storage/installed.lock is just metadata (install date, etc.) — on
        // some hosts a redeploy can wipe the gitignored storage/ directory
        // while config.php survives, and that must not force the installer
        // to run again (it re-inserts demo content on top of what's in the
        // DB). Self-heal the lock file instead of depending on it here.
        if (!is_file(self::lockFile())) {
            $dir = dirname(self::lockFile());
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @file_put_contents(self::lockFile(), json_encode([
                'installed_at' => date(DATE_ATOM),
                'site_name' => (string) ($config['app']['name'] ?? 'Jura CMS'),
                'recovered' => true,
            ], JSON_PRETTY_PRINT));
        }

        return true;
    }

    private static function coreTablesExist(array $config): bool
    {
        $db = (array) ($config['database'] ?? []);
        if (empty($db)) {
            return false;
        }

        try {
            $pdo = \db_connect($db);
            $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($db['prefix'] ?? 'jura_')) ?: 'jura_';
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($prefix . 'users'));
            return (bool) ($stmt && $stmt->fetchColumn());
        } catch (\Throwable) {
            return false;
        }
    }

    public static function installerExists(): bool
    {
        return is_dir(BASE_PATH . '/install');
    }

    public static function installWarning(): ?string
    {
        if (!self::isInstalled() || !self::installerExists()) {
            return null;
        }

        return 'Установка завершена, но каталог /install/ всё ещё доступен. Удалите или отключите его в продакшене.';
    }
}
