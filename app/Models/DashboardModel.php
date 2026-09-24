<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\ModuleLoader;
use PDO;

/** Aggregate counts for the /admin dashboard cards. */
final class DashboardModel
{
    public static function stats(PDO $pdo): array
    {
        $stats = [];
        foreach (['pages', 'posts', 'media_files', 'users', 'redirects', 'menu_items'] as $table) {
            try {
                $stats[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table($table))->fetchColumn();
            } catch (\Throwable $e) {
                $stats[$table] = 0;
            }
        }
        try {
            $stats['leads'] = (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table('form_submissions'))->fetchColumn();
        } catch (\Throwable $e) {
            $stats['leads'] = 0;
        }
        return array_merge($stats, ModuleLoader::hookCollect('admin_stats', $pdo));
    }
}
