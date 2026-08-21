<?php

declare(strict_types=1);

namespace Core\Updater;

final class Updater
{
    private const REPO = 'medvestnik/juracms';
    private const API_URL = 'https://api.github.com/repos/' . self::REPO . '/releases/latest';
    private const USER_AGENT = 'JuraCMS-Updater';

    public static function readVersionFile(): string
    {
        $version = trim((string) @file_get_contents(BASE_PATH . '/VERSION'));
        return $version !== '' ? $version : '0.0.0';
    }

    /**
     * Fetch the latest release from GitHub and compare it to the installed
     * version. Returns null fields (and an 'error') if the check itself
     * failed (network/API issue) rather than throwing, since this runs from
     * an admin button click and a failure here shouldn't crash the page.
     */
    public static function checkForUpdates(): array
    {
        $current = self::readVersionFile();
        $result = [
            'current_version' => $current,
            'latest_version' => null,
            'has_update' => false,
            'release_notes' => null,
            'release_url' => null,
            'zip_url' => null,
            'published_at' => null,
            'error' => null,
        ];

        $body = self::httpGet(self::API_URL, ['Accept: application/vnd.github+json']);
        if ($body === null) {
            $result['error'] = 'Не вдалося звернутися до GitHub API. Перевірте, чи дозволені вихідні HTTPS-з\'єднання на цьому хостингу.';
            return $result;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['tag_name'])) {
            $result['error'] = 'GitHub API повернув неочікувану відповідь.';
            return $result;
        }

        $latest = ltrim((string) $data['tag_name'], 'vV');
        $result['latest_version'] = $latest;
        $result['has_update'] = version_compare($latest, $current, '>');
        $result['release_notes'] = (string) ($data['body'] ?? '');
        $result['release_url'] = (string) ($data['html_url'] ?? '');
        $result['published_at'] = (string) ($data['published_at'] ?? '');
        $result['zip_url'] = (string) ($data['zipball_url'] ?? '');

        return $result;
    }

    /**
     * Download the latest release archive and extract it over the current
     * install. GitHub's auto-generated source archives only contain files
     * that are actually tracked in git, so config.php/storage//uploads//
     * logs//cache// (all gitignored) are simply absent from the archive and
     * are never touched by this — no separate "preserve user data" logic
     * needed. A zip backup of the current tracked-ish files is written to
     * storage/backups/ first in case something needs to be rolled back.
     */
    public static function runAutomaticUpdate(): array
    {
        $result = [
            'ok' => false,
            'step' => null,
            'message' => '',
            'backup_path' => null,
            'files_applied' => 0,
        ];

        if (!class_exists(\ZipArchive::class)) {
            $result['step'] = 'zip-extension';
            $result['message'] = 'Розширення PHP ZipArchive недоступне на цьому хостингу — автоматичне оновлення неможливе. Оновіть файли вручну.';
            return $result;
        }

        $check = self::checkForUpdates();
        if ($check['error'] !== null) {
            $result['step'] = 'check';
            $result['message'] = $check['error'];
            return $result;
        }
        if (empty($check['zip_url'])) {
            $result['step'] = 'check';
            $result['message'] = 'Не вдалося визначити посилання на архів релізу.';
            return $result;
        }

        @set_time_limit(300);
        @ignore_user_abort(true);

        $tmpDir = sys_get_temp_dir() . '/jura-update-' . bin2hex(random_bytes(4));
        $zipPath = $tmpDir . '/release.zip';
        if (!@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            $result['step'] = 'tmp';
            $result['message'] = 'Не вдалося створити тимчасову директорію.';
            return $result;
        }

        $zipData = self::httpGet($check['zip_url'], ['Accept: application/vnd.github+json']);
        if ($zipData === null || $zipData === '') {
            $result['step'] = 'download';
            $result['message'] = 'Не вдалося завантажити архів релізу.';
            self::rrmdir($tmpDir);
            return $result;
        }
        file_put_contents($zipPath, $zipData);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $result['step'] = 'unzip';
            $result['message'] = 'Не вдалося розпакувати архів релізу.';
            self::rrmdir($tmpDir);
            return $result;
        }
        $extractDir = $tmpDir . '/extracted';
        @mkdir($extractDir, 0775, true);
        $zip->extractTo($extractDir);
        $zip->close();

        // GitHub source archives wrap everything in a single top-level
        // "<owner>-<repo>-<sha>/" directory.
        $entries = array_values(array_diff(scandir($extractDir) ?: [], ['.', '..']));
        if (count($entries) !== 1 || !is_dir($extractDir . '/' . $entries[0])) {
            $result['step'] = 'unzip';
            $result['message'] = 'Несподівана структура архіву релізу.';
            self::rrmdir($tmpDir);
            return $result;
        }
        $sourceDir = $extractDir . '/' . $entries[0];

        if (!is_file($sourceDir . '/index.php') || !is_file($sourceDir . '/VERSION')) {
            $result['step'] = 'verify';
            $result['message'] = 'Архів релізу не схожий на дистрибутив Jura CMS (немає index.php/VERSION).';
            self::rrmdir($tmpDir);
            return $result;
        }

        $backupPath = self::backupCurrentInstall();
        $result['backup_path'] = $backupPath;

        $applied = self::copyRecursive($sourceDir, BASE_PATH);
        $result['files_applied'] = $applied;

        self::rrmdir($tmpDir);

        $result['ok'] = true;
        $result['message'] = sprintf(
            'Оновлено до v%s. Файлів застосовано: %d. Папка config/ не чіпається автооновленням.%s',
            $check['latest_version'],
            $applied,
            $backupPath ? ' Резервна копія: ' . basename($backupPath) : ''
        );

        return $result;
    }

    private static function backupCurrentInstall(): ?string
    {
        if (!class_exists(\ZipArchive::class)) {
            return null;
        }
        $backupDir = BASE_PATH . '/storage/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0775, true);
        }
        if (!is_dir($backupDir) || !is_writable($backupDir)) {
            return null;
        }

        $path = $backupDir . '/pre-update-' . date('Ymd-His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE) !== true) {
            return null;
        }

        $skip = ['.git', 'storage', 'uploads', 'cache', 'tmp', 'logs', 'vendor', 'node_modules'];
        $baseLen = strlen(BASE_PATH) + 1;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), $baseLen);
            $topLevel = explode('/', $relative)[0];
            if (in_array($topLevel, $skip, true)) {
                continue;
            }
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $relative);
            }
        }
        $zip->close();

        return $path;
    }

    /**
     * Paths (relative to BASE_PATH, top-level segment only) that an
     * automatic core update must never overwrite. config/ in particular
     * holds per-site choices (active theme, editor, etc.) that a site owner
     * sets through the admin UI or by hand — blindly copying the generic
     * release's config/ui.php over it silently resets things like a custom
     * frontend_theme back to "default", breaking any page whose view relies
     * on that theme's own CSS classes. This was exactly what happened to a
     * customized fork after clicking "update now".
     */
    private const UPDATE_PROTECTED_PATHS = ['config'];

    private static function copyRecursive(string $from, string $to): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $baseLen = strlen($from) + 1;
        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), $baseLen);
            $topLevel = explode('/', $relative, 2)[0];
            if (in_array($topLevel, self::UPDATE_PROTECTED_PATHS, true)) {
                continue;
            }
            $target = $to . '/' . $relative;
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
            if (@copy($item->getPathname(), $target)) {
                $count++;
            }
        }
        return $count;
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

    private static function httpGet(string $url, array $headers = []): ?string
    {
        $headers[] = 'User-Agent: ' . self::USER_AGENT;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code < 200 || $code >= 300) {
                return null;
            }
            return (string) $body;
        }

        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => 120,
                    'follow_location' => 1,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            return $body === false ? null : $body;
        }

        return null;
    }

    // Legacy helpers kept for anything still referencing the old install
    // finalize flow.
    public static function installedVersion(): string
    {
        return (string) ($_SESSION['installed_version'] ?? self::readVersionFile());
    }

    public static function needsManualFinalize(): bool
    {
        return version_compare(self::readVersionFile(), self::installedVersion(), '>');
    }

    public static function finalizeManualUpdate(): void
    {
        $_SESSION['installed_version'] = self::readVersionFile();
    }
}
