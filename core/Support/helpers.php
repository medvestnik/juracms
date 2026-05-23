<?php

declare(strict_types=1);

if (!function_exists('jura_table')) {
    function jura_table(string $table): string
    {
        $prefix = 'jura_';
        $configFile = BASE_PATH . '/config.php';

        if (is_file($configFile)) {
            $config = require $configFile;

            if (is_array($config) && isset($config['database']['prefix'])) {
                $prefix = (string) $config['database']['prefix'];
            }
        }

        $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: '';
        $table = str_replace('`', '', ltrim($table, '_'));

        if ($prefix !== '' && str_starts_with($table, $prefix)) {
            return '`' . $table . '`';
        }

        return '`' . $prefix . $table . '`';
    }
}
