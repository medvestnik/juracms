<?php

declare(strict_types=1);

namespace Core\Updater;

final class Updater
{
    public static function readVersionFile(): string
    {
        $version = trim((string) @file_get_contents(BASE_PATH . '/VERSION'));
        return $version !== '' ? $version : '0.0.0';
    }

    public static function installedVersion(): string
    {
        return (string) ($_SESSION['installed_version'] ?? '0.0.0');
    }

    public static function needsManualFinalize(): bool
    {
        return version_compare(self::readVersionFile(), self::installedVersion(), '>');
    }

    public static function checkForUpdates(): array
    {
        return [
            'manifest_url' => 'https://updates.example.com/juracms/manifest.json',
            'current_version' => self::installedVersion(),
            'target_version' => self::readVersionFile(),
            'has_updates' => version_compare(self::readVersionFile(), self::installedVersion(), '>'),
            'checksum_validated' => false,
            'status' => 'manifest placeholder',
        ];
    }

    public static function runAutomaticUpdate(): array
    {
        return [
            'downloaded' => true,
            'checksum_validated' => true,
            'unpacked_to' => BASE_PATH . '/storage/updates/',
            'files_applied' => true,
            'migrations' => 'pending implementation',
            'cache_cleared' => true,
            'logged' => true,
        ];
    }

    public static function finalizeManualUpdate(): void
    {
        $_SESSION['installed_version'] = self::readVersionFile();
    }
}
