<?php

declare(strict_types=1);

require_once BASE_PATH . '/install/Installer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete-install') {
    Installer::complete();
    header('Location: /admin');
    exit;
}

$steps = Installer::steps();
include BASE_PATH . '/install/views/wizard.php';
