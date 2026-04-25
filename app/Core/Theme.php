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
}
