<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\DashboardModel;
use PDO;

final class DashboardController
{
    public static function index(PDO $pdo): never
    {
        view_admin('dashboard', ['title' => 'Дашборд', 'stats' => DashboardModel::stats($pdo)]);
        exit;
    }
}
