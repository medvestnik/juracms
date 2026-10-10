<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ModuleLoader;
use App\Core\ModuleUpdates;
use PDO;

final class ModuleController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'module_update') {
                $slug = (string) ($_POST['slug'] ?? '');
                $result = ModuleUpdates::update($slug);
                session_flash($result['ok'] ? 'modules_success' : 'modules_error', $result['message']);
                redirect('/admin/modules');
            }
            if ($action === 'upload') {
                $file = $_FILES['module_zip'] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                    session_flash('modules_error', 'Не вдалося завантажити файл.');
                } elseif (strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
                    session_flash('modules_error', 'Очікується ZIP-архів.');
                } else {
                    $upload = ModuleLoader::installFromZip($file['tmp_name']);
                    session_flash($upload['ok'] ? 'modules_success' : 'modules_error', $upload['message']);
                }
                redirect('/admin/modules');
            }
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug'] ?? ''));
            if ($slug && $action === 'install') {
                ModuleLoader::install($slug, $pdo);
            } elseif ($slug && $action === 'uninstall') {
                ModuleLoader::uninstall($slug, $pdo);
            } elseif ($slug && $action === 'enable') {
                ModuleLoader::toggle($slug, true, $pdo);
            } elseif ($slug && $action === 'disable') {
                ModuleLoader::toggle($slug, false, $pdo);
            }
            redirect('/admin/modules');
        }
        $updates = $_SESSION['module_update_checks'] ?? [];
        if ($method === 'GET' && isset($_GET['check_versions'])) {
            $updates = ['gitdeploy' => ModuleUpdates::check('gitdeploy')];
            $_SESSION['module_update_checks'] = $updates;
        }
        view_admin('modules', [
            'module_updates' => $updates,
            'title' => 'Модулі',
            'modules' => ModuleLoader::available($pdo),
            'flash_success' => session_flash('modules_success'),
            'flash_error' => session_flash('modules_error'),
        ]);
        exit;
    }
}
