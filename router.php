<?php
// PHP built-in server router
// Usage: php -S localhost:8080 router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve real files directly (assets, images, etc.)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Route /install/ to install/index.php
if (str_starts_with($uri, '/install')) {
    $installFile = __DIR__ . '/install/index.php';
    if (file_exists($installFile)) {
        require $installFile;
        return true;
    }
}

// Everything else goes through index.php
require __DIR__ . '/index.php';
return true;
