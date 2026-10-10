<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Updates bundled modules from the official JuraCMS repository.
 * Site-specific modules are deliberately excluded.
 */
final class ModuleUpdates
{
    private const API = 'https://raw.githubusercontent.com/medvestnik/juracms/main/modules/';
    private const ARCHIVE = 'https://github.com/medvestnik/juracms/archive/refs/heads/main.zip';

    private static function bundledDirectory(string $slug): ?string
    {
        $map = ['gitdeploy' => 'GitDeploy'];
        return $map[$slug] ?? null;
    }

    public static function check(string $slug): array
    {
        $dir = self::bundledDirectory($slug);
        if ($dir === null) {
            return ['state' => 'unsupported', 'message' => 'Для цього модуля немає офіційного джерела оновлень.'];
        }
        $localPath = BASE_PATH . '/modules/' . $dir . '/module.json';
        $local = json_decode((string) @file_get_contents($localPath), true);
        if (!is_array($local) || ($local['slug'] ?? '') !== $slug) {
            return ['state' => 'error', 'message' => 'Локальний маніфест пошкоджено.'];
        }
        $remote = self::download(self::API . rawurlencode($dir) . '/module.json');
        $data = $remote !== null ? json_decode($remote, true) : null;
        if (!is_array($data) || ($data['slug'] ?? '') !== $slug || empty($data['version'])) {
            return ['state' => 'unknown', 'current' => (string) ($local['version'] ?? '0.0.0'), 'message' => 'Не вдалося перевірити GitHub.'];
        }
        $current = (string) ($local['version'] ?? '0.0.0');
        $latest = (string) $data['version'];
        return [
            'state' => version_compare($latest, $current, '>') ? 'outdated' : 'current',
            'current' => $current,
            'latest' => $latest,
        ];
    }

    public static function update(string $slug): array
    {
        $dir = self::bundledDirectory($slug);
        if ($dir === null) {
            return ['ok' => false, 'message' => 'Оновлення цього модуля недоступне.'];
        }
        $check = self::check($slug);
        if ($check['state'] !== 'outdated') {
            return ['ok' => false, 'message' => 'Оновлення недоступне: ' . ($check['message'] ?? 'версія актуальна.')];
        }
        if (!class_exists(\ZipArchive::class)) {
            return ['ok' => false, 'message' => 'Не встановлено PHP ZipArchive.'];
        }
        $zipData = self::download(self::ARCHIVE);
        if ($zipData === null) {
            return ['ok' => false, 'message' => 'Не вдалося завантажити архів JuraCMS.'];
        }
        $tmp = sys_get_temp_dir() . '/jura-module-' . bin2hex(random_bytes(8));
        if (!@mkdir($tmp, 0700, true)) {
            return ['ok' => false, 'message' => 'Не вдалося створити тимчасовий каталог.'];
        }
        $zipFile = $tmp . '/source.zip';
        file_put_contents($zipFile, $zipData);
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            self::remove($tmp);
            return ['ok' => false, 'message' => 'Некоректний ZIP.'];
        }
        $prefix = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^([^/]+)/modules/' . preg_quote($dir, '#') . '/module\\.json$#', $name, $m)) {
                $prefix = $m[1] . '/modules/' . $dir . '/';
                break;
            }
        }
        if ($prefix === null) {
            $zip->close();
            self::remove($tmp);
            return ['ok' => false, 'message' => 'Модуль відсутній в архіві.'];
        }
        $stage = $tmp . '/new';
        mkdir($stage, 0700);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!str_starts_with($name, $prefix)) continue;
            $relative = substr($name, strlen($prefix));
            if ($relative === '' || str_ends_with($relative, '/')) continue;
            if (str_contains($relative, '..') || str_starts_with($relative, '/')) continue;
            $dest = $stage . '/' . $relative;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0700, true);
            file_put_contents($dest, $zip->getFromIndex($i));
        }
        $zip->close();
        $manifest = json_decode((string) @file_get_contents($stage . '/module.json'), true);
        if (!is_array($manifest) || ($manifest['slug'] ?? '') !== $slug
            || version_compare((string) ($manifest['version'] ?? '0'), (string) $check['latest'], '<')) {
            self::remove($tmp);
            return ['ok' => false, 'message' => 'Версія архіву не відповідає перевіреній. Спробуйте пізніше.'];
        }
        $target = BASE_PATH . '/modules/' . $dir;
        $backupDir = BASE_PATH . '/storage/backups/modules';
        if (!is_dir($backupDir)) @mkdir($backupDir, 0700, true);
        $backup = $backupDir . '/' . $dir . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
        if (!is_dir($backupDir) || !@rename($target, $backup)) {
            self::remove($tmp);
            return ['ok' => false, 'message' => 'Не вдалося створити резервну копію модуля.'];
        }
        // Temporary directories are often on another filesystem (/tmp vs /var/www).
        // PHP rename() cannot move a directory across filesystems, so copy to a
        // sibling of the target first, then atomically rename on the same volume.
        $localStage = BASE_PATH . '/modules/.' . $dir . '-update-' . bin2hex(random_bytes(6));
        if (!self::copyDirectory($stage, $localStage)) {
            self::remove($localStage);
            $restored = @rename($backup, $target);
            self::remove($tmp);
            return ['ok' => false, 'message' => 'Не вдалося скопіювати файли модуля.' . ($restored ? ' Попередню версію відновлено.' : ' УВАГА: відновлення не вдалося; резервна копія: ' . $backup)];
        }
        if (!@rename($localStage, $target)) {
            self::remove($localStage);
            $restored = @rename($backup, $target);
            self::remove($tmp);
            return ['ok' => false, 'message' => 'Не вдалося активувати модуль.' . ($restored ? ' Попередню версію відновлено.' : ' УВАГА: відновлення не вдалося; резервна копія: ' . $backup)];
        }
        self::remove($tmp);
        return ['ok' => true, 'message' => 'Модуль ' . $dir . ' оновлено до v' . $manifest['version'] . '. Резервна копія: ' . $backup];
    }

    private static function download(string $url): ?string
    {
        if (!function_exists('curl_init')) return null;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'JuraCMS-ModuleUpdater',
        ]);
        $data = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return is_string($data) && $http >= 200 && $http < 300 ? $data : null;
    }

    private static function copyDirectory(string $from, string $to): bool
    {
        if (!@mkdir($to, 0755)) return false;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($from) + 1);
            $dest = $to . '/' . $relative;
            if ($item->isDir()) {
                if (!is_dir($dest) && !@mkdir($dest, 0755, true)) return false;
            } elseif (!$item->isLink()) {
                if (!@copy($item->getPathname(), $dest)) return false;
                @chmod($dest, 0644);
            } else {
                return false;
            }
        }
        return true;
    }

    private static function remove(string $directory): void
    {
        if (!is_dir($directory)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $entry) {
            $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
        }
        @rmdir($directory);
    }
}
