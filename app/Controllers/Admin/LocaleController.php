<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\LocaleModel;
use PDO;

final class LocaleController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create') {
                $code = strtolower(trim((string) ($_POST['code'] ?? '')));
                if ($code !== '') {
                    LocaleModel::create($pdo, $code, trim((string) ($_POST['name'] ?? '')), trim((string) ($_POST['native_name'] ?? '')), (int) ($_POST['sort_order'] ?? 0));
                }
            } elseif ($action === 'update') {
                LocaleModel::update(
                    $pdo,
                    (int) ($_POST['id'] ?? 0),
                    trim((string) ($_POST['name'] ?? '')),
                    trim((string) ($_POST['native_name'] ?? '')),
                    (int) ($_POST['sort_order'] ?? 0),
                    isset($_POST['is_active'])
                );
            } elseif ($action === 'set_default') {
                LocaleModel::setDefault($pdo, (int) ($_POST['id'] ?? 0));
            } elseif ($action === 'delete') {
                if (!LocaleModel::delete($pdo, (int) ($_POST['id'] ?? 0))) {
                    session_flash('error', 'Не можна видалити мову за замовчуванням.');
                }
            }
            LocaleModel::syncActiveSetting($pdo);
            redirect('/admin/locales');
        }
        view_admin('locales', ['title' => 'Мови сайту', 'locales' => jura_available_locales($pdo, '*'), 'flash_error' => session_flash('error')]);
        exit;
    }
}
