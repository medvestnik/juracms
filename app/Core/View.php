<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function admin(string $view, array $data = []): void
    {
        self::render('admin', $view, $data);
    }

    public static function frontend(string $view, array $data = []): void
    {
        self::render('frontend', $view, $data);
    }

    private static function render(string $area, string $view, array $data): void
    {
        $data['title'] = $data['title'] ?? 'Jura CMS';

        if ($area === 'admin') {
            $viewFile = Theme::resolveAdminView($view);
            $layoutName = (string) ($data['layout'] ?? 'app');
            $layoutFile = Theme::resolveAdminLayout($layoutName);
            $data['assets'] = $data['assets'] ?? ($layoutName === 'installer' ? Asset::installerAssets() : Asset::adminAssets());
        } else {
            $viewFile = Theme::resolveFrontendView($view);
            $layoutFile = Theme::resolveFrontendLayout((string) ($data['layout'] ?? 'app'));
            $data['assets'] = $data['assets'] ?? Asset::frontendAssets();
        }

        if (!is_file($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        if (!is_file($layoutFile)) {
            http_response_code(500);
            echo 'Layout not found';
            return;
        }

        extract($data, EXTR_SKIP);
        include $layoutFile;
    }

    public static function component(string $component, array $data = []): void
    {
        $file = Theme::adminPath('components/' . trim($component, '/') . '.php');
        if (!is_file($file)) {
            return;
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}
