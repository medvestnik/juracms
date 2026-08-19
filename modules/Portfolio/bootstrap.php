<?php

declare(strict_types=1);

use App\Core\Asset;
use App\Core\ModuleLoader;
use App\Core\Theme;

if (!function_exists('portfolio_module_items')) {
    function portfolio_module_items(PDO $pdo, string $kind, bool $homepageOnly = false, string $locale = ''): array
    {
        try {
            $homepage = $homepageOnly ? ' AND featured_home=1' : '';
            $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('portfolio_items') . " WHERE kind=? AND status='published' AND (locale=? OR locale=''){$homepage} ORDER BY sort_order,id");
            $stmt->execute([$kind, $locale]);
            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }
}

require_once __DIR__ . '/data.php';

$render = static function (string $area, string $view, array $data): void {
    $viewFile = __DIR__ . '/views/' . $view . '.php';
    $layoutFile = $area === 'admin' ? Theme::resolveAdminLayout('app') : Theme::resolveFrontendLayout('app');
    $assets = $area === 'admin' ? Asset::adminAssets() : Asset::frontendAssets();
    extract($data, EXTR_SKIP);
    include $layoutFile;
};

ModuleLoader::register('portfolio', [
    'admin_nav_group' => 'Портфоліо',
    'admin_nav' => [
        '/admin/portfolio/projects' => 'Проєкти',
        '/admin/portfolio/projects/create' => 'Додати проєкт',
        '/admin/portfolio/items' => 'Портфоліо',
        '/admin/portfolio/items/create' => 'Додати портфоліо',
    ],
    'install' => static function (PDO $pdo): void {
        $table = jura_table('portfolio_items');
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kind ENUM('project','portfolio') NOT NULL,
            title VARCHAR(191) NOT NULL,
            category VARCHAR(120) NOT NULL DEFAULT '',
            description TEXT NULL,
            url VARCHAR(500) NOT NULL DEFAULT '',
            link_label VARCHAR(120) NOT NULL DEFAULT '',
            status_label VARCHAR(120) NOT NULL DEFAULT '',
            color VARCHAR(30) NOT NULL DEFAULT 'ink',
            status ENUM('published','draft') NOT NULL DEFAULT 'published',
            featured_home TINYINT(1) NOT NULL DEFAULT 0,
            locale VARCHAR(16) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX kind_status_order (kind,status,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        portfolio_module_insert_missing_rows($pdo);
        $pdo->prepare('UPDATE ' . jura_table('menu_items') . " SET url='/portfolio' WHERE title='Портфолио'")->execute();
        $pdo->prepare('UPDATE ' . jura_table('menu_items') . " SET url='/projects' WHERE title='Проекты'")->execute();
    },
    'ensure_schema' => static function (PDO $pdo): void {
        portfolio_module_ensure_schema($pdo);
        $pdo->prepare('UPDATE ' . jura_table('menu_items') . " SET url='/portfolio' WHERE title='Портфолио' AND url<>'/portfolio'")->execute();
        $pdo->prepare('UPDATE ' . jura_table('menu_items') . " SET url='/projects' WHERE title='Проекты' AND url<>'/projects'")->execute();
    },
    'handle_admin' => static function (string $path, string $method, PDO $pdo) use ($render): bool {
        if (!preg_match('#^/admin/portfolio/(projects|items)(?:/(create|\\d+/edit))?$#', $path, $m)) return false;
        $kind = $m[1] === 'projects' ? 'project' : 'portfolio';
        $segment = $m[2] ?? '';
        $base = '/admin/portfolio/' . $m[1];
        if ($method === 'POST') {
            if (($_POST['action'] ?? '') === 'delete') {
                $pdo->prepare('DELETE FROM ' . jura_table('portfolio_items') . ' WHERE id=? AND kind=?')->execute([(int) $_POST['id'], $kind]);
            } else {
                $values = [$kind, trim((string)$_POST['title']), trim((string)$_POST['category']), trim((string)$_POST['description']), trim((string)$_POST['url']), trim((string)$_POST['link_label']), trim((string)($_POST['status_label'] ?? '')), preg_replace('/[^a-z-]/', '', (string)$_POST['color']), isset($_POST['published']) ? 'published' : 'draft', isset($_POST['featured_home']) ? 1 : 0, (string)($_POST['locale'] ?? ''), (int)($_POST['sort_order'] ?? 0)];
                $id = (int) ($_POST['id'] ?? 0);
                if ($id) {
                    $values[] = $id;
                    $pdo->prepare('UPDATE ' . jura_table('portfolio_items') . ' SET kind=?,title=?,category=?,description=?,url=?,link_label=?,status_label=?,color=?,status=?,featured_home=?,locale=?,sort_order=? WHERE id=?')->execute($values);
                } else {
                    $pdo->prepare('INSERT INTO ' . jura_table('portfolio_items') . ' (kind,title,category,description,url,link_label,status_label,color,status,featured_home,locale,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);
                }
            }
            redirect($base);
        }
        if ($segment === 'create' || str_ends_with($segment, '/edit')) {
            $item = [];
            if ($segment !== 'create') {
                $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('portfolio_items') . ' WHERE id=? AND kind=?');
                $stmt->execute([(int)$segment, $kind]); $item = $stmt->fetch() ?: [];
            }
            $allLocales = $pdo->query('SELECT code,native_name FROM ' . jura_table('locales') . ' ORDER BY sort_order,id')->fetchAll();
            $render('admin', 'editor', ['title' => ($item ? 'Редагувати' : 'Додати') . ($kind === 'project' ? ' проєкт' : ' портфоліо'), 'item'=>$item, 'kind'=>$kind, 'base'=>$base, 'all_locales'=>$allLocales]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('portfolio_items') . ' WHERE kind=? ORDER BY sort_order,id'); $stmt->execute([$kind]);
            $render('admin', 'index', ['title'=>$kind === 'project' ? 'Проєкти' : 'Портфоліо', 'items'=>$stmt->fetchAll(), 'kind'=>$kind, 'base'=>$base]);
        }
        return true;
    },
    'handle_frontend' => static function (string $path, PDO $pdo) use ($render): bool {
        // current_locale() is defined earlier in index.php and already
        // loaded by the time module hooks run -- strip the /en, /ru style
        // prefix the same way core page/post routing does.
        [$locale, $path] = current_locale($pdo, $path);
        if (!in_array($path, ['/portfolio','/projects'], true)) return false;
        $kind = $path === '/projects' ? 'project' : 'portfolio';
        $settings = cms_settings($pdo);
        $siteName = $settings['site_name'] ?? '';
        $render('frontend', 'catalog', ['title'=>$kind === 'project' ? 'Проєкти' : 'Портфоліо', 'meta_description'=>$kind === 'project' ? trim('Власні цифрові проєкти ' . $siteName) : trim('Роботи в портфоліо ' . $siteName), 'kind'=>$kind, 'items'=>portfolio_module_items($pdo,$kind,false,$locale), 'settings'=>$settings, 'locale'=>$locale]);
        return true;
    },
]);
