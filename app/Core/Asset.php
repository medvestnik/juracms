<?php

declare(strict_types=1);

namespace App\Core;

final class Asset
{
    public static function ui(string $key, string $default = ''): string
    {
        return (string) config_value('ui.assets.' . $key, $default);
    }

    public static function adminAssets(): array
    {
        return [
            'css' => [
                self::ui('jura_ui_css'),
                '/public/assets/jura-ui/themes/' . config_value('ui.appearance.default_mode', 'light') . '.css',
                self::ui('admin_css'),
            ],
            'js' => [
                self::ui('jura_ui_js'),
                self::ui('admin_js'),
            ],
        ];
    }

    public static function installerAssets(): array
    {
        return [
            'css' => [
                self::ui('jura_ui_css'),
                self::ui('installer_css'),
            ],
            'js' => [self::ui('jura_ui_js')],
        ];
    }

    public static function frontendAssets(): array
    {
        return [
            'css' => [
                self::ui('jura_ui_css'),
                '/public/assets/jura-ui/themes/' . config_value('ui.appearance.default_mode', 'light') . '.css',
            ],
            'js' => [self::ui('jura_ui_js')],
        ];
    }
}
