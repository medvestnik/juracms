<?php

declare(strict_types=1);

use App\Core\View;

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
        return \Core\Installer\Runtime::installWarning();
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
