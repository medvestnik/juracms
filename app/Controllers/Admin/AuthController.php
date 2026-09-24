<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Schema;
use App\Models\UserModel;
use Core\Installer\Runtime as InstallerRuntime;
use RuntimeException;
use Throwable;

final class AuthController
{
    public static function login(string $method): never
    {
        if (!InstallerRuntime::isInstalled()) {
            redirect('/install/');
        }
        $dbConfig = (array) cms_config('database', []);
        if ($method === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            try {
                $pdo = db_connect($dbConfig);
                Schema::ensure($pdo);
                $user = UserModel::findForLogin($pdo, $email);
                if (!$user || $user['status'] !== 'active' || !password_verify($password, (string) $user['password_hash'])) {
                    throw new RuntimeException('bad credentials');
                }
                $_SESSION['admin_user_id'] = (int) $user['id'];
                $_SESSION['admin_user_email'] = $user['email'];
                redirect('/admin');
            } catch (RuntimeException) {
                session_flash('auth_error', 'Неверный email/пароль.');
                redirect('/admin/login');
            } catch (Throwable $e) {
                session_flash('auth_error', 'Помилка бази даних: ' . $e->getMessage());
                redirect('/admin/login');
            }
        }
        if (admin_is_authenticated()) {
            redirect('/admin');
        }
        view_admin('login', ['title' => 'Вхід', 'layout' => 'auth', 'error' => session_flash('auth_error')]);
        exit;
    }

    public static function logout(string $method): never
    {
        if ($method !== 'POST') {
            http_response_code(405);
            exit;
        }
        unset($_SESSION['admin_user_id'], $_SESSION['admin_user_email']);
        redirect('/admin/login');
    }
}
