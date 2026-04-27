<?php

declare(strict_types=1);

namespace Core\Installer;

final class Runtime
{
    public static function lockFile(): string
    {
        return BASE_PATH . '/storage/installed.lock';
    }

    public static function isInstalled(): bool
    {
        return is_file(self::lockFile());
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
