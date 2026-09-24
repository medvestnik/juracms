<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SettingModel;
use PDO;

final class SettingsController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            self::save($pdo);
            session_flash('success', 'Settings saved.');
            redirect('/admin/settings');
        }
        view_admin('settings', ['title' => 'Налаштування', 'settings' => SettingModel::all($pdo), 'success' => session_flash('success')]);
        exit;
    }

    private static function save(PDO $pdo): void
    {
        foreach ($_POST['settings'] ?? [] as $key => $value) {
            $group = str_contains((string) $key, 'locale') ? 'localization' : 'system';
            if (str_starts_with((string) $key, 'contact_') || str_starts_with((string) $key, 'social_') || (string) $key === 'contact_address' || (string) $key === 'show_phone') {
                $group = 'contacts';
            }
            if (in_array((string) $key, ['google_maps_embed', 'gtm_id', 'ga4_id', 'google_ads_id', 'fb_pixel_id', 'fb_access_token'], true)) {
                $group = 'integrations';
            }
            if (str_starts_with((string) $key, 'telegram_') || (string) $key === 'notification_email_to') {
                $group = 'notifications';
            }
            if ((string) $key === 'thankyou_page') {
                $group = 'forms';
            }
            if ((string) $key === 'blog_per_page') {
                $group = 'system';
            }
            if (in_array((string) $key, ['admin_jura_theme', 'admin_dark_mode'], true)) {
                $group = 'system';
            }
            // menu_* keys
            if (str_starts_with((string) $key, 'menu_') || in_array((string) $key, ['site_name', 'site_url'], true)) {
                $group = 'system';
            }
            SettingModel::set($pdo, (string) $key, is_array($value) ? implode(',', $value) : $value, $group);
        }
        // Checkboxes not sent when unchecked — save explicit 0
        if (!isset($_POST['settings']['admin_dark_mode'])) {
            SettingModel::set($pdo, 'admin_dark_mode', '0', 'system');
        }
        if (!isset($_POST['settings']['show_phone'])) {
            SettingModel::set($pdo, 'show_phone', '0', 'contacts');
        }
    }

    public static function updateLibrary(PDO $pdo): never
    {
        $lib = $_POST['lib'] ?? '';
        $result = '';
        $tmpDir = sys_get_temp_dir() . '/jura-lib-update-' . $lib . '-' . time();
        if (!exec_available()) {
            $result = 'Функція exec вимкнена на цьому хостингу — оновлення бібліотек через адмінку недоступне.';
        } elseif ($lib === 'juraui') {
            $cloneCmd = 'git clone --depth 1 https://github.com/medvestnik/juraui.git ' . escapeshellarg($tmpDir) . ' 2>&1';
            exec($cloneCmd, $out, $code);
            if ($code === 0) {
                $src = $tmpDir . '/assets/css/jura-ui.css';
                $dst = BASE_PATH . '/public/assets/jura-ui/jura-ui.css';
                if (file_exists($src)) {
                    copy($src, $dst);
                    $result = 'Jura UI CSS оновлено успішно.';
                } else {
                    $result = 'Помилка: файл jura-ui.css не знайдено в репозиторії.';
                }
                // Copy JS components
                $jsSrc = [
                    $tmpDir . '/src/js/core/theme.js',
                    $tmpDir . '/src/js/components/dropdown.js',
                    $tmpDir . '/src/js/components/modal.js',
                    $tmpDir . '/src/js/components/tabs.js',
                ];
                $jsContent = '';
                foreach ($jsSrc as $f) {
                    if (file_exists($f)) {
                        $jsContent .= preg_replace('/^export function /m', 'function ', file_get_contents($f)) . "\n";
                    }
                }
                if ($jsContent) {
                    $jsContent .= "\ndocument.addEventListener('DOMContentLoaded',function(){initTheme&&initTheme();initDropdowns&&initDropdowns();initModals&&initModals();initTabs&&initTabs();});\n";
                    file_put_contents(BASE_PATH . '/public/assets/jura-ui/jura-ui.js', $jsContent);
                    $result .= ' JS оновлено.';
                }
                exec('rm -rf ' . escapeshellarg($tmpDir));
            } else {
                $result = 'Помилка git clone: ' . implode(' ', $out);
            }
        } elseif ($lib === 'simple-js-editor') {
            $cloneCmd = 'git clone --depth 1 https://github.com/medvestnik/simple-js-editor.git ' . escapeshellarg($tmpDir) . ' 2>&1';
            exec($cloneCmd, $out, $code);
            if ($code === 0) {
                $buildOut = [];
                exec('cd ' . escapeshellarg($tmpDir) . ' && npm install --silent 2>&1 && npm run build 2>&1', $buildOut, $buildCode);
                if ($buildCode === 0 && file_exists($tmpDir . '/dist/simple-js-editor.umd.js')) {
                    copy($tmpDir . '/dist/simple-js-editor.umd.js', BASE_PATH . '/public/assets/admin/editor/simple-js-editor.js');
                    copy($tmpDir . '/dist/style.css', BASE_PATH . '/public/assets/admin/editor/simple-js-editor.css');
                    $result = 'Simple JS Editor оновлено успішно.';
                } else {
                    $result = 'Помилка збірки: ' . implode(' ', array_slice($buildOut, -5));
                }
                exec('rm -rf ' . escapeshellarg($tmpDir));
            } else {
                $result = 'Помилка git clone: ' . implode(' ', $out);
            }
        } else {
            $result = 'Невідома бібліотека.';
        }
        view_admin('settings', ['title' => 'Налаштування', 'settings' => SettingModel::all($pdo), 'success' => session_flash('success'), 'lib_update_result' => $result]);
        exit;
    }
}
