<?php

declare(strict_types=1);

require_once __DIR__ . '/GitDeployModule.php';

use App\Core\ModuleLoader;
use App\Core\Theme;
use App\Core\Asset;

$gitDeployRender = static function (string $view, array $data): void {
    $viewFile = __DIR__ . '/views/' . $view . '.php';
    $layoutFile = Theme::resolveAdminLayout('app');
    $data['assets'] = Asset::adminAssets();
    extract($data, EXTR_SKIP);
    include $layoutFile;
};

function git_deploy_handle_admin(string $path, string $method, PDO $pdo, callable $render): bool
{
    if ($path === '/admin/gitdeploy') {
        if (!shell_available()) {
            $render('main', ['title' => 'Git Deploy', 'shell_disabled' => true]);
            return true;
        }
        $isRepo = git_deploy_is_repo($pdo);
        $render('main', [
            'title' => 'Git Deploy',
            'shell_disabled' => false,
            'is_repo' => $isRepo,
            'settings' => git_deploy_settings($pdo),
            'info' => $isRepo ? git_deploy_get_info($pdo) : null,
            'status' => $isRepo ? git_deploy_get_status($pdo) : [],
            'logs' => git_deploy_get_logs($pdo),
            'config_info' => git_deploy_config_info($pdo),
            'flash_success' => session_flash('gd_success'),
            'flash_error' => session_flash('gd_error'),
            'flash_output' => session_flash('gd_output'),
            'flash_ssh_key' => session_flash('gd_ssh_key'),
        ]);
        return true;
    }

    if (!shell_available()) {
        return false;
    }

    if ($path === '/admin/gitdeploy/init' && $method === 'POST') {
        git_deploy_save_settings($pdo, $_POST);
        $result = git_deploy_init_repo($pdo, $_POST);
        session_flash($result['success'] ? 'gd_success' : 'gd_error', $result['success'] ? 'Репозиторій підключено.' : ($result['error'] ?? 'Помилка'));
        session_flash('gd_output', (string) ($result['output'] ?? ($result['error'] ?? '')));
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/pull' && $method === 'POST') {
        $result = git_deploy_pull($pdo);
        session_flash($result['success'] ? 'gd_success' : 'gd_error', $result['success'] ? 'git pull виконано.' : ($result['error'] ?? 'Помилка'));
        session_flash('gd_output', (string) ($result['output'] ?? ''));
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/commit' && $method === 'POST') {
        $files = (array) ($_POST['files'] ?? []);
        $message = trim((string) ($_POST['message'] ?? ''));
        if (!$files) {
            session_flash('gd_error', 'Виберіть хоча б один файл.');
        } elseif ($message === '') {
            session_flash('gd_error', 'Введіть повідомлення коміту.');
        } else {
            $result = git_deploy_commit_and_push($pdo, $files, $message);
            session_flash($result['success'] ? 'gd_success' : 'gd_error', $result['success'] ? 'Зміни закомічено й запушено.' : ($result['error'] ?? 'Помилка'));
            session_flash('gd_output', (string) ($result['output'] ?? ''));
        }
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/gitignore' && $method === 'POST') {
        $files = (array) ($_POST['files'] ?? []);
        if ($files) {
            $result = git_deploy_add_to_gitignore($pdo, $files);
            session_flash('gd_success', $result['output']);
        }
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/reset' && $method === 'POST') {
        $result = git_deploy_reset_repo($pdo);
        session_flash($result['success'] ? 'gd_success' : 'gd_error', $result['success'] ? 'Репозиторій скинуто.' : ($result['error'] ?? 'Помилка'));
        session_flash('gd_output', (string) ($result['output'] ?? ($result['error'] ?? '')));
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/generate-ssh-key' && $method === 'POST') {
        $result = git_deploy_generate_ssh_key();
        if ($result['success']) {
            session_flash('gd_ssh_key', (string) ($result['public_key']));
            session_flash('gd_success', 'SSH-ключ згенеровано. Скопіюйте публічний ключ нижче і додайте його в GitHub → Settings → Deploy keys.');
        } else {
            session_flash('gd_error', $result['error']);
        }
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/save-schema' && $method === 'POST') {
        $result = git_deploy_save_schema_to_repo($pdo);
        session_flash($result['success'] ? 'gd_success' : 'gd_error', $result['success'] ? "Схему БД збережено у db_schema.md ({$result['tables']} таблиць)." : 'Не вдалося записати файл.');
        redirect('/admin/gitdeploy');
        return true;
    }

    if ($path === '/admin/gitdeploy/diff' && $method === 'GET') {
        header('Content-Type: application/json');
        echo json_encode(['diff' => git_deploy_get_diff($pdo, (string) ($_GET['file'] ?? ''))]);
        exit;
    }

    if ($path === '/admin/gitdeploy/status' && $method === 'GET') {
        header('Content-Type: application/json');
        echo json_encode(['files' => git_deploy_get_status($pdo), 'info' => git_deploy_is_repo($pdo) ? git_deploy_get_info($pdo, true) : null]);
        exit;
    }

    return false;
}

ModuleLoader::register('gitdeploy', [
    'name' => 'Git Deploy',
    'ensure_schema' => 'git_deploy_ensure_schema',
    'install' => 'git_deploy_ensure_schema',
    'handle_admin' => static function (string $path, string $method, PDO $pdo) use ($gitDeployRender): bool {
        return git_deploy_handle_admin($path, $method, $pdo, $gitDeployRender);
    },
    'admin_nav_group' => 'Git Deploy',
    'admin_nav' => [
        '/admin/gitdeploy' => 'Git Deploy',
    ],
]);
