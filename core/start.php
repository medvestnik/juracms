<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

if (!class_exists(App\Core\View::class)) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        $baseDir = BASE_PATH . '/app/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    });
}

require_once BASE_PATH . '/app/helpers.php';
require_once BASE_PATH . '/core/Installer/Runtime.php';
require_once BASE_PATH . '/core/Updater/Updater.php';

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
