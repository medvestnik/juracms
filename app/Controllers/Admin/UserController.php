<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\UserModel;
use PDO;

final class UserController
{
    public static function index(PDO $pdo): never
    {
        view_admin('users', ['title' => 'Користувачі', 'users' => UserModel::all($pdo), 'flash_success' => session_flash('success'), 'flash_error' => session_flash('error')]);
        exit;
    }

    public static function create(PDO $pdo): never
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = trim((string) ($_POST['password'] ?? ''));
        $role = in_array($_POST['role'] ?? '', ['admin', 'editor']) ? $_POST['role'] : 'editor';
        if (!$email || !$pass) {
            session_flash('error', 'Email та пароль обов\'язкові.');
            redirect('/admin/users');
        }
        try {
            UserModel::create($pdo, $email, password_hash($pass, PASSWORD_DEFAULT), $role);
            session_flash('success', 'Користувача створено.');
        } catch (\Throwable $e) {
            session_flash('error', 'Email вже існує.');
        }
        redirect('/admin/users');
    }

    public static function delete(PDO $pdo, int $id): never
    {
        if ($id !== (int) $_SESSION['admin_user_id']) {
            UserModel::delete($pdo, $id);
            session_flash('success', 'Користувача видалено.');
        } else {
            session_flash('error', 'Не можна видалити власний акаунт.');
        }
        redirect('/admin/users');
    }

    public static function updatePassword(PDO $pdo, int $id): never
    {
        $pass = trim((string) ($_POST['password'] ?? ''));
        if (strlen($pass) < 6) {
            session_flash('error', 'Пароль мінімум 6 символів.');
            redirect('/admin/users');
        }
        UserModel::updatePassword($pdo, $id, password_hash($pass, PASSWORD_DEFAULT));
        session_flash('success', 'Пароль змінено.');
        redirect('/admin/users');
    }

    public static function toggle(PDO $pdo, int $id): never
    {
        UserModel::toggleStatus($pdo, $id);
        redirect('/admin/users');
    }
}
