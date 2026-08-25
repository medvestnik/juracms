<?php

declare(strict_types=1);

namespace App\Core;

final class Theme
{
    public static function adminTheme(): string
    {
        return (string) config_value('ui.admin_theme', 'jura');
    }

    public static function frontendTheme(): string
    {
        return (string) config_value('ui.frontend_theme', 'default');
    }

    public static function adminPath(string $kind): string
    {
        return BASE_PATH . '/themes/admin/' . self::adminTheme() . '/' . trim($kind, '/');
    }

    public static function frontendPath(string $kind): string
    {
        return BASE_PATH . '/themes/frontend/' . self::frontendTheme() . '/' . trim($kind, '/');
    }

    public static function resolveAdminView(string $view): string
    {
        $path = self::adminPath('views/' . trim($view, '/') . '.php');
        return is_file($path) ? $path : self::adminPath('views/404.php');
    }

    public static function resolveFrontendView(string $view): string
    {
        $path = self::frontendPath('views/' . trim($view, '/') . '.php');
        return is_file($path) ? $path : self::frontendPath('views/page.php');
    }

    public static function resolveAdminLayout(string $layout): string
    {
        return self::adminPath('layouts/' . trim($layout, '/') . '.php');
    }

    public static function resolveFrontendLayout(string $layout): string
    {
        return self::frontendPath('layouts/' . trim($layout, '/') . '.php');
    }

    /**
     * Unpacks an uploaded frontend-theme .zip into themes/frontend/ so it
     * shows up in the theme list, ready for "Активувати". Same convention
     * and accepted shapes as ModuleLoader::installFromZip(): either
     * theme.json at the zip root, or a single top-level folder containing
     * theme.json — that folder's own name is used as-is for
     * themes/frontend/<name>, preserving whatever casing the theme author
     * used rather than re-deriving it from the slug.
     */
    public static function installFromZip(string $zipPath): array
    {
        $result = ['ok' => false, 'message' => '', 'slug' => null];

        if (!class_exists(\ZipArchive::class)) {
            $result['message'] = 'Розширення PHP ZipArchive недоступне на цьому хостингу — завантаження тем архівом неможливе.';
            return $result;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $result['message'] = 'Не вдалося відкрити ZIP-архів.';
            return $result;
        }

        $tmpDir = sys_get_temp_dir() . '/jura-theme-upload-' . bin2hex(random_bytes(4));
        if (!@mkdir($tmpDir, 0775, true)) {
            $zip->close();
            $result['message'] = 'Не вдалося створити тимчасову директорію.';
            return $result;
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $themeDir = null;
        $folderName = null;
        if (is_file($tmpDir . '/theme.json')) {
            $themeDir = $tmpDir;
        } else {
            $entries = array_values(array_diff(scandir($tmpDir) ?: [], ['.', '..']));
            if (count($entries) === 1 && is_dir($tmpDir . '/' . $entries[0]) && is_file($tmpDir . '/' . $entries[0] . '/theme.json')) {
                $themeDir = $tmpDir . '/' . $entries[0];
                $folderName = $entries[0];
            }
        }

        if ($themeDir === null) {
            self::rrmdir($tmpDir);
            $result['message'] = 'Архів не схожий на тему JuraCMS (немає theme.json у корені або в єдиній кореневій папці).';
            return $result;
        }

        $manifest = json_decode((string) file_get_contents($themeDir . '/theme.json'), true);
        if (!is_array($manifest) || empty($manifest['slug'])) {
            self::rrmdir($tmpDir);
            $result['message'] = 'theme.json пошкоджений або без поля "slug".';
            return $result;
        }

        $folderName = $folderName ?? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $manifest['slug']);
        if ($folderName === '' || str_contains($folderName, '..')) {
            self::rrmdir($tmpDir);
            $result['message'] = 'Некоректна назва директорії теми в архіві.';
            return $result;
        }

        $target = BASE_PATH . '/themes/frontend/' . $folderName;
        self::rrmdir($target);
        self::copyRecursive($themeDir, $target);
        self::rrmdir($tmpDir);

        $result['ok'] = true;
        $result['slug'] = (string) $manifest['slug'];
        $result['message'] = 'Тема «' . (string) ($manifest['name'] ?? $manifest['slug']) . '» завантажена в themes/frontend/' . $folderName . '. Активуйте її нижче.';
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
}
