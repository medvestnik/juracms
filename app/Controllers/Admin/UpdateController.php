<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Updater\Updater;

final class UpdateController
{
    public static function index(string $method): never
    {
        $currentVersion = trim((string) @file_get_contents(BASE_PATH . '/VERSION')) ?: '0.1.0';
        $lockData = [];
        $lockFile = BASE_PATH . '/storage/installed.lock';
        if (is_file($lockFile)) {
            $lockData = json_decode((string) file_get_contents($lockFile), true) ?: [];
        }
        $gitRemote = trim(safe_shell_exec('git -C ' . escapeshellarg(BASE_PATH) . ' remote get-url origin 2>/dev/null'));
        $gitBranch = trim(safe_shell_exec('git -C ' . escapeshellarg(BASE_PATH) . ' rev-parse --abbrev-ref HEAD 2>/dev/null'));
        $gitLastCommit = trim(safe_shell_exec('git -C ' . escapeshellarg(BASE_PATH) . ' log -1 --format="%h %s (%cr)" 2>/dev/null'));

        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'git_pull') {
                if (!shell_available()) {
                    session_flash('upd_error', 'Функція shell_exec вимкнена на цьому хостингу — git pull через адмінку недоступний.');
                } else {
                    $output = safe_shell_exec('git -C ' . escapeshellarg(BASE_PATH) . ' pull 2>&1');
                    session_flash('upd_success', 'git pull: ' . ($output !== '' ? $output : 'виконано'));
                }
                redirect('/admin/updates');
            }
            if ($action === 'check_updates') {
                $_SESSION['update_check'] = Updater::checkForUpdates();
                redirect('/admin/updates');
            }
            if ($action === 'update_now') {
                $updateResult = Updater::runAutomaticUpdate();
                if ($updateResult['ok']) {
                    session_flash('upd_success', $updateResult['message']);
                    unset($_SESSION['update_check']);
                } else {
                    session_flash('upd_error', $updateResult['message']);
                }
                redirect('/admin/updates');
            }
            if ($action === 'restore_backup') {
                $restoreResult = Updater::restoreBackup((string) ($_POST['filename'] ?? ''));
                session_flash($restoreResult['ok'] ? 'upd_success' : 'upd_error', $restoreResult['message']);
                redirect('/admin/updates');
            }
        }

        $errorLogFile = BASE_PATH . '/logs/php-error.log';
        $errorLogTail = '';
        if (is_file($errorLogFile)) {
            $lines = file($errorLogFile, FILE_IGNORE_NEW_LINES) ?: [];
            $errorLogTail = implode("\n", array_slice($lines, -100));
        }
        view_admin('updates', [
            'title' => 'Оновлення',
            'current_version' => $currentVersion,
            'installed_at' => $lockData['installed_at'] ?? '',
            'git_remote' => $gitRemote,
            'git_branch' => $gitBranch,
            'git_last_commit' => $gitLastCommit,
            'error_log_tail' => $errorLogTail,
            'update_check' => $_SESSION['update_check'] ?? null,
            'backups' => Updater::listBackups(),
            'flash_success' => session_flash('upd_success'),
            'flash_error' => session_flash('upd_error'),
        ]);
        exit;
    }
}
