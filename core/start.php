<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

if (PHP_SAPI !== 'cli') {
    // display_errors stays whatever the host has it set to (should be off in
    // production) — this only makes sure errors/warnings/fatals are also
    // captured in the project's own logs/ folder, since on managed hosting
    // the webserver's own error log often isn't reachable by the site owner.
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    if (is_dir($logDir) && is_writable($logDir)) {
        ini_set('log_errors', '1');
        ini_set('error_log', $logDir . '/php-error.log');
    }
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
require_once BASE_PATH . '/core/Support/helpers.php';
require_once BASE_PATH . '/core/Installer/Runtime.php';
require_once BASE_PATH . '/core/Updater/Updater.php';

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    // Some shared-hosting setups make the filesystem session path unwritable
    // for this site's PHP-FPM pool user (global ini path, or even our own
    // storage/sessions if it ends up owned by a different deploy user).
    // Prefer DB-backed sessions once the app is installed, since we already
    // know the database connection works; fall back to files otherwise.
    $dbSessionsReady = false;
    if (\Core\Installer\Runtime::isInstalled()) {
        try {
            $sessionPdo = db_connect((array) cms_config('database', []));
            $sessionTable = jura_table('sessions');
            $sessionPdo->exec("CREATE TABLE IF NOT EXISTS {$sessionTable} (id VARCHAR(191) PRIMARY KEY, data MEDIUMTEXT NULL, last_activity INT UNSIGNED) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            session_set_save_handler(new \App\Core\DbSessionHandler($sessionPdo, $sessionTable), true);
            $dbSessionsReady = true;
        } catch (\Throwable) {
            $dbSessionsReady = false;
        }
    }
    if (!$dbSessionsReady) {
        // Some shared-hosting setups point session.save_path at a directory the
        // site's PHP-FPM pool user can't write to. Use a project-local, known
        // writable directory instead of relying on the global ini setting.
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0775, true);
        }
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }
    }
    session_start();
}
