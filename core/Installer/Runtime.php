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

        return is_file(self::configFile()) && $installedFlag === true && is_file(self::lockFile());
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
