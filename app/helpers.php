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
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);
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
