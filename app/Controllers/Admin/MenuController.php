<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\MenuModel;
use App\Models\SettingModel;
use PDO;

final class MenuController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            self::handleAction($pdo);
            redirect('/admin/menus');
        }
        $s = SettingModel::all($pdo);
        view_admin('menus', [
            'title' => 'Меню',
            'menus' => MenuModel::allMenus($pdo),
            'items' => MenuModel::allItems($pdo),
            'menu_pages' => MenuModel::linkablePages($pdo),
            'menu_header' => $s['menu_header'] ?? 'main',
            'menu_footer' => $s['menu_footer'] ?? 'main',
            'all_locales' => jura_available_locales($pdo),
        ]);
        exit;
    }

    private static function handleAction(PDO $pdo): void
    {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create_menu') {
            MenuModel::createMenu($pdo, slugify((string) $_POST['code']), $_POST['name']);
        }
        if ($action === 'add_item') {
            MenuModel::addItem(
                $pdo,
                (int) $_POST['menu_id'],
                ($_POST['parent_id'] ?? '') !== '' ? (int) $_POST['parent_id'] : null,
                $_POST['title'],
                $_POST['url'],
                $_POST['target'] ?? '_self',
                (int) ($_POST['sort_order'] ?? 0),
                $_POST['status'] ?? 'active',
                $_POST['locale'] ?? ''
            );
        }
        if ($action === 'delete_item') {
            MenuModel::deleteItem($pdo, (int) $_POST['id']);
        }
        if ($action === 'delete_menu') {
            MenuModel::deleteMenu($pdo, (int) $_POST['id']);
        }
        if ($action === 'rename_menu') {
            MenuModel::renameMenu($pdo, (int) $_POST['id'], $_POST['name']);
        }
        if ($action === 'edit_item') {
            MenuModel::editItem($pdo, (int) $_POST['id'], $_POST['title'], $_POST['url'], (int) ($_POST['sort_order'] ?? 0), $_POST['locale'] ?? '');
        }
    }
}
