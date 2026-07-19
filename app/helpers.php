<?php

declare(strict_types=1);

use App\Core\View;
use Core\Installer\Runtime as InstallerRuntime;

if (!function_exists('config_value')) {
    function config_value(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $parts = explode('.', $key);
        $root = (string) array_shift($parts);

        if (!array_key_exists($root, $cache)) {
            $file = BASE_PATH . '/config/' . $root . '.php';
            $cache[$root] = is_file($file) ? (array) require $file : [];
        }

        $value = $cache[$root];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}


if (!function_exists('cms_config')) {
    function cms_config(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $file = BASE_PATH . '/config.php';
            $data = is_file($file) ? require $file : [];
            $config = is_array($data) ? $data : [];
        }

        if ($key === null || $key == '') {
            return $config;
        }

        $value = $config;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        static $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ie','ж'=>'zh','з'=>'z',
            'и'=>'y','і'=>'i','ї'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p',
            'р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch',
            'ь'=>'','ю'=>'iu','я'=>'ia','ъ'=>'','э'=>'e','ё'=>'e','ы'=>'y',
        ];
        $value = mb_strtolower(trim($value));
        $value = strtr($value, $map);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('view_admin')) {
    function view_admin(string $view, array $data = []): void
    {
        View::admin($view, $data);
    }
}

if (!function_exists('view_frontend')) {
    function view_frontend(string $view, array $data = []): void
    {
        View::frontend($view, $data);
    }
}

if (!function_exists('installer_warning')) {
    function installer_warning(): ?string
    {
        return InstallerRuntime::installWarning();
    }
}

if (!function_exists('editor_config')) {
    function editor_config(): array
    {
        return (array) config_value('editor', []);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION)) {
            return 'cli-csrf-token';
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf_token'];
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('session_flash')) {
    function session_flash(string $key, ?string $value = null): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        if (!array_key_exists($key, $_SESSION['_flash'])) {
            return null;
        }

        $flash = (string) $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return $flash;
    }
}

if (!function_exists('db_connect')) {
    function db_connect(array $db): PDO
    {
        $charset = (string) ($db['charset'] ?? 'utf8mb4');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $charset);
        return new PDO($dsn, (string) $db['username'], (string) $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}

if (!function_exists('admin_is_authenticated')) {
    function admin_is_authenticated(): bool
    {
        return !empty($_SESSION['admin_user_id']);
    }
}

if (!function_exists('admin_require_auth')) {
    function admin_require_auth(): void
    {
        if (!InstallerRuntime::isInstalled()) {
            redirect('/install/');
        }

        if (!admin_is_authenticated()) {
            redirect('/admin/login');
        }
    }
}
