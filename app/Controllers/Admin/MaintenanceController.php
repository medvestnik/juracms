<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\MenuModel;
use App\Models\PageModel;
use App\Models\RouteModel;
use App\Models\SettingModel;
use PDO;
use Throwable;

final class MaintenanceController
{
    public static function index(PDO $pdo, string $method): never
    {
        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            $msg = '';
            try {
                $msg = self::runAction($pdo, $action);
            } catch (Throwable $e) {
                session_flash('maint_error', $e->getMessage());
                redirect('/admin/maintenance');
            }
            session_flash('maint_success', $msg);
            redirect('/admin/maintenance');
        }
        view_admin('maintenance', ['title' => 'Обслуговування', 'flash_success' => session_flash('maint_success'), 'flash_error' => session_flash('maint_error')]);
        exit;
    }

    private static function runAction(PDO $pdo, string $action): string
    {
        if ($action === 'fix_duplicates') {
            // Keep the page with the lowest id for each slug, delete duplicates without routes
            $pages = PageModel::all($pdo);
            $seen = [];
            $deleted = 0;
            foreach ($pages as $p) {
                if (isset($seen[$p['slug']])) {
                    if (!RouteModel::hasFor($pdo, 'page', (int) $p['id'])) {
                        PageModel::delete($pdo, (int) $p['id']);
                        $deleted++;
                    }
                } else {
                    $seen[$p['slug']] = $p['id'];
                }
            }
            return "Видалено дублів: {$deleted}";
        }
        if ($action === 'fix_templates') {
            PageModel::setTemplateBySlug($pdo, 'home', 'home');
            PageModel::setTemplateBySlug($pdo, 'contacts', 'contacts');
            PageModel::setTemplateBySlug($pdo, 'about', 'about');
            return 'Шаблони оновлено: home → home, contacts → contacts, about → about';
        }
        if ($action === 'rebuild_routes') {
            $pageRoutes = ['home' => '/', 'about' => '/about', 'contacts' => '/contacts', 'blog' => '/blog'];
            foreach ($pageRoutes as $slug => $routePath) {
                $page = PageModel::findBySlug($pdo, $slug);
                if ($page) {
                    RouteModel::save($pdo, $routePath, 'page', (int) $page['id']);
                }
            }
            return 'Роути відновлено';
        }
        if ($action === 'rebuild_menu') {
            $menuId = MenuModel::findIdByCode($pdo, 'main');
            if (!$menuId) {
                $menuId = MenuModel::insertMenu($pdo, 'main', 'Main');
            }
            MenuModel::clearItems($pdo, $menuId);
            $items = [['Головна', '/'], ['Про нас', '/about'], ['Блог', '/blog'], ['Контакти', '/contacts']];
            foreach ($items as $i => [$label, $url]) {
                MenuModel::insertItem($pdo, $menuId, $label, $url, $i + 1, 'active');
            }
            return 'Меню оновлено: ' . count($items) . ' пунктів';
        }
        if ($action === 'refresh_demo_content') {
            $settings = SettingModel::all($pdo);
            $siteName = $settings['site_name'] ?? 'Jura CMS';
            $homeContent = '<p>' . e($siteName) . ' &mdash; це легка система керування сайтом із класичною установкою в корінь хостингу та сучасною адмін-панеллю. Створюйте сторінки, ведіть блог, керуйте медіатекою та меню &mdash; все з коробки.</p><p>Ця сторінка, як і сторінки &laquo;Про нас&raquo; та &laquo;Контакти&raquo;, &mdash; демонстраційний контент. Відредагуйте або видаліть його в розділі <strong>Сторінки</strong> адмін-панелі.</p>';
            $aboutContent = '<p>' . e($siteName) . ' працює на Jura CMS &mdash; системі керування сайтом, що поєднує простоту класичних движків із зручністю адмін-панелі нового покоління.</p><p>У цьому розділі зазвичай розповідають історію компанії, місію та команду. Замініть цей текст власним описом у розділі <strong>Сторінки</strong>.</p><div class="feature-grid" style="margin-top:1.75rem"><div class="feature-card"><div class="feature-card__icon">🎯</div><h3>Місія</h3><p>Дати простий та зрозумілий інструмент для створення й розвитку сайту.</p></div><div class="feature-card"><div class="feature-card__icon">⚡</div><h3>Швидкість</h3><p>Мінімум залежностей, встановлення в корінь хостингу за кілька хвилин.</p></div><div class="feature-card"><div class="feature-card__icon">🤝</div><h3>Підтримка</h3><p>Оновлення та документація для впевненого старту й розвитку проєкту.</p></div></div>';
            $contactsContent = '<p>Залишились питання? Напишіть нам &mdash; форма нижче надсилає повідомлення прямо на пошту адміністратора сайту.</p>';
            PageModel::setContentBySlug($pdo, 'home', $homeContent);
            PageModel::setContentBySlug($pdo, 'about', $aboutContent);
            PageModel::setContentBySlug($pdo, 'contacts', $contactsContent);
            return 'Демо-контент оновлено на сторінках home, about, contacts';
        }
        if ($action === 'install_demo_data') {
            $created = jura_seed_demo_posts($pdo, (int) $_SESSION['admin_user_id']);
            return $created > 0 ? "Додано демо-публікацій: {$created}" : 'Демо-публікації вже встановлені.';
        }
        if ($action === 'clear_cache') {
            // Already flushed by the blanket admin-POST hook in index.php;
            // this just gives a clear confirmation message.
            return 'Кеш сторінок очищено.';
        }
        return '';
    }
}
