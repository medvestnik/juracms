<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Theme;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

final class ThemeController
{
    public static function index(?string $themeSlug, string $method): never
    {
        if ($method === 'POST' && !$themeSlug) {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'upload') {
                $file = $_FILES['theme_zip'] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                    session_flash('themes_error', 'Не вдалося завантажити файл.');
                } elseif (strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
                    session_flash('themes_error', 'Очікується ZIP-архів.');
                } else {
                    $upload = Theme::installFromZip($file['tmp_name']);
                    session_flash($upload['ok'] ? 'themes_success' : 'themes_error', $upload['message']);
                }
                redirect('/admin/themes');
            }
            $activateSlug = preg_replace('/[^a-z0-9_-]/', '', $_POST['slug'] ?? '');
            if ($activateSlug && $action === 'activate') {
                // Update config/ui.php
                $cfgFile = BASE_PATH . '/config/ui.php';
                $cfgContent = file_get_contents($cfgFile);
                $cfgContent = preg_replace(
                    "/'frontend_theme'\s*=>\s*'[^']*'/",
                    "'frontend_theme' => '" . addslashes($activateSlug) . "'",
                    $cfgContent
                );
                file_put_contents($cfgFile, $cfgContent);
            }
            redirect('/admin/themes');
        }

        // Scan themes/frontend/ for available themes
        $themeBase = BASE_PATH . '/themes/frontend';
        $activeTheme = (string) config_value('ui.frontend_theme', 'default');
        $allThemes = [];
        foreach (glob($themeBase . '/*/theme.json') ?: [] as $manifestFile) {
            $data = json_decode(file_get_contents($manifestFile), true);
            if (is_array($data) && !empty($data['slug'])) {
                $dir = dirname($manifestFile);
                $data['active'] = ($data['slug'] === $activeTheme);
                $data['path']   = $dir;
                $allThemes[]    = $data;
            }
        }

        if ($themeSlug) {
            // Detail page: show info + file tree
            $theme = null;
            foreach ($allThemes as $t) {
                if ($t['slug'] === $themeSlug) {
                    $theme = $t;
                    break;
                }
            }
            if (!$theme) {
                redirect('/admin/themes');
            }

            // Build file tree relative to theme root
            $files = [];
            $baseLen = strlen($theme['path']) + 1;
            $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme['path'], FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if ($file->isFile()) {
                    $rel = substr($file->getPathname(), $baseLen);
                    if (!str_starts_with($rel, '.git')) {
                        $files[] = $rel;
                    }
                }
            }
            sort($files);

            view_admin('theme-detail', ['title' => 'Тема: ' . ($theme['name'] ?? $themeSlug), 'theme' => $theme, 'files' => $files]);
        } else {
            view_admin('themes', [
                'title' => 'Шаблони',
                'themes' => $allThemes,
                'active_theme' => $activeTheme,
                'flash_success' => session_flash('themes_success'),
                'flash_error' => session_flash('themes_error'),
            ]);
        }
        exit;
    }
}
