<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', __DIR__);
}

$autoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once BASE_PATH . '/core/start.php';
require_once BASE_PATH . '/install/Installer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete-install') {
    Installer::complete();
    header('Location: /admin');
    exit;
}

$steps = Installer::steps();
include BASE_PATH . '/install/views/wizard.php';
