<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\RedirectModel;
use PDO;

final class RedirectController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            if (($_POST['action'] ?? '') === 'delete') {
                RedirectModel::delete($pdo, (int) $_POST['id']);
            } else {
                $source = ensure_path((string) $_POST['source_path']);
                $target = ensure_path((string) $_POST['target_path']);
                RedirectModel::upsert($pdo, $source, $target, (int) ($_POST['status_code'] ?? 301), isset($_POST['is_active']), $_POST['notes'] ?? '');
            }
            redirect('/admin/redirects');
        }
        view_admin('redirects', ['title' => 'Редіректи', 'redirects' => RedirectModel::all($pdo)]);
        exit;
    }
}
