<?php

declare(strict_types=1);

use App\Core\View;
use Core\Installer\Runtime as InstallerRuntime;

if (!function_exists('config_value')) {
    function config_value(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $parts = explode('.', $key);
        $root = (string) array_shift($parts);

        if (!array_key_exists($root, $cache)) {
            $file = BASE_PATH . '/config/' . $root . '.php';
            $cache[$root] = is_file($file) ? (array) require $file : [];
        }

        $value = $cache[$root];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}


if (!function_exists('cms_config')) {
    function cms_config(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $file = BASE_PATH . '/config.php';
            $data = is_file($file) ? require $file : [];
            $config = is_array($data) ? $data : [];
        }

        if ($key === null || $key == '') {
            return $config;
        }

        $value = $config;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        static $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ie','ж'=>'zh','з'=>'z',
            'и'=>'y','і'=>'i','ї'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p',
            'р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch',
            'ь'=>'','ю'=>'iu','я'=>'ia','ъ'=>'','э'=>'e','ё'=>'e','ы'=>'y',
        ];
        $value = mb_strtolower(trim($value));
        $value = strtr($value, $map);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('view_admin')) {
    function view_admin(string $view, array $data = []): void
    {
        View::admin($view, $data);
    }
}

if (!function_exists('view_frontend')) {
    function view_frontend(string $view, array $data = []): void
    {
        View::frontend($view, $data);
    }
}

if (!function_exists('installer_warning')) {
    function installer_warning(): ?string
    {
        return InstallerRuntime::installWarning();
    }
}

if (!function_exists('editor_config')) {
    function editor_config(): array
    {
        return (array) config_value('editor', []);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION)) {
            return 'cli-csrf-token';
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf_token'];
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('session_flash')) {
    function session_flash(string $key, ?string $value = null): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        if (!array_key_exists($key, $_SESSION['_flash'])) {
            return null;
        }

        $flash = (string) $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return $flash;
    }
}

if (!function_exists('db_connect')) {
    function db_connect(array $db): PDO
    {
        $charset = (string) ($db['charset'] ?? 'utf8mb4');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $charset);
        return new PDO($dsn, (string) $db['username'], (string) $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }
}

if (!function_exists('shell_available')) {
    function shell_available(): bool
    {
        static $available = null;
        if ($available === null) {
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
            $available = function_exists('shell_exec') && !in_array('shell_exec', $disabled, true);
        }
        return $available;
    }
}

if (!function_exists('safe_shell_exec')) {
    function safe_shell_exec(string $command): string
    {
        if (!shell_available()) {
            return '';
        }
        try {
            return (string) (@shell_exec($command) ?? '');
        } catch (\Throwable) {
            return '';
        }
    }
}

if (!function_exists('exec_available')) {
    function exec_available(): bool
    {
        static $available = null;
        if ($available === null) {
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
            $available = function_exists('exec') && !in_array('exec', $disabled, true);
        }
        return $available;
    }
}

if (!function_exists('admin_is_authenticated')) {
    function admin_is_authenticated(): bool
    {
        return !empty($_SESSION['admin_user_id']);
    }
}

if (!function_exists('admin_require_auth')) {
    function admin_require_auth(): void
    {
        if (!InstallerRuntime::isInstalled()) {
            redirect('/install/');
        }

        if (!admin_is_authenticated()) {
            redirect('/admin/login');
        }
    }
}

if (!function_exists('jura_available_locales')) {
    /** Read-only locale list for populating a "Мова" select -- never
     * fatals if jura_locales is (temporarily) missing, just degrades to
     * no locale options instead of a white page. */
    function jura_available_locales(PDO $pdo, string $columns = 'code,name,native_name'): array
    {
        try {
            return $pdo->query('SELECT ' . $columns . ' FROM ' . jura_table('locales') . ' ORDER BY sort_order,id')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }
}

if (!function_exists('jura_seed_thankyou_page')) {
    // Callable both from the installer (before index.php's function set
    // exists) and from the admin, so it only depends on helpers loaded by
    // core/start.php. Idempotent: pages/routes have no unique slug/path
    // constraint to lean on for pages, so it checks before inserting.
    function jura_seed_thankyou_page(PDO $pdo, int $authorId): void
    {
        $existing = $pdo->prepare('SELECT id FROM ' . jura_table('pages') . ' WHERE slug=? LIMIT 1');
        $existing->execute(['thankyou']);
        $pageId = (int) $existing->fetchColumn();

        if (!$pageId) {
            $content = '<div style="text-align:center;padding:60px 20px"><div style="font-size:56px;margin-bottom:16px">&#10003;</div><h1 style="font-size:2rem;margin-bottom:16px">Дякуємо за звернення!</h1><p style="font-size:1.1rem;max-width:480px;margin:0 auto 32px">Ваше повідомлення отримано. Ми зв&#39;яжемося з вами найближчим часом.</p><a href="/">На головну</a></div>';
            $ins = $pdo->prepare('INSERT INTO ' . jura_table('pages') . ' (author_id,title,slug,content,status,meta_title,meta_description,sort_order,published_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
            $ins->execute([$authorId, 'Дякуємо за звернення', 'thankyou', $content, 'published', 'Дякуємо за звернення', 'Ваше повідомлення отримано.', 99]);
            $pageId = (int) $pdo->lastInsertId();
        }

        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('routes') . ' (path,entity_type,entity_id,status) VALUES (?,?,?,?)')
            ->execute(['/thankyou', 'page', $pageId, 'active']);
        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?)')
            ->execute(['thankyou_page', '/thankyou', 'string', 'forms']);
    }
}

if (!function_exists('jura_seed_demo_posts')) {
    /** The lorem-ipsum blog posts previously delivered via migrations/003_demo_posts.sql
     * (and, separately, install/index.php's own inline demo posts). Idempotent —
     * safe to call again on a site that already has some or all of them. */
    function jura_seed_demo_posts(PDO $pdo, int $authorId): int
    {
        $catId = (int) $pdo->query('SELECT id FROM ' . jura_table('post_categories') . " WHERE slug='news' LIMIT 1")->fetchColumn();
        if (!$catId) {
            $pdo->prepare('INSERT INTO ' . jura_table('post_categories') . ' (title,slug,description) VALUES (?,?,?)')->execute(['Новини', 'news', '']);
            $catId = (int) $pdo->lastInsertId();
        }

        $posts = [
            ['Lorem Ipsum Dolor Sit Amet', 'lorem-ipsum-dolor-sit-amet', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>', 4],
            ['Consectetur Adipiscing Elit', 'consectetur-adipiscing-elit', 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.', '<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>', 3],
            ['Sed Do Eiusmod Tempor', 'sed-do-eiusmod-tempor', 'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.', '<p>Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.</p>', 2],
            ['Ut Enim Ad Minim Veniam', 'ut-enim-ad-minim-veniam', 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum.', '<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p>', 1],
            ['Duis Aute Irure Dolor', 'duis-aute-irure-dolor', 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat.', '<p>Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.</p>', 0],
        ];

        // Read directly rather than via active_locales()/cms_settings() --
        // those live in index.php, which the installer never loads.
        $localeStmt = $pdo->query("SELECT setting_value FROM " . jura_table('settings') . " WHERE setting_key='default_locale' LIMIT 1");
        $defaultLocale = (string) ($localeStmt->fetchColumn() ?: 'uk');
        $created = 0;
        foreach ($posts as [$title, $slug, $excerpt, $content, $daysAgo]) {
            $exists = $pdo->prepare('SELECT id FROM ' . jura_table('posts') . ' WHERE slug=? LIMIT 1');
            $exists->execute([$slug]);
            $postId = (int) $exists->fetchColumn();
            if (!$postId) {
                $ins = $pdo->prepare('INSERT INTO ' . jura_table('posts') . ' (author_id,title,slug,excerpt,content,status,meta_title,meta_description,sort_order,locale,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW() - INTERVAL ? DAY)');
                $ins->execute([$authorId, $title, $slug, $excerpt, $content, 'published', $title, $excerpt, 0, $defaultLocale, $daysAgo]);
                $postId = (int) $pdo->lastInsertId();
                $created++;
            }
            $pdo->prepare('INSERT IGNORE INTO ' . jura_table('post_category_relations') . ' (post_id,category_id) VALUES (?,?)')->execute([$postId, $catId]);
            $pdo->prepare('INSERT IGNORE INTO ' . jura_table('routes') . ' (path,entity_type,entity_id,status) VALUES (?,?,?,?)')->execute(['/blog/' . $slug, 'post', $postId, 'active']);
        }
        return $created;
    }
}

// ── Frontend page cache ──────────────────────────────────────────────────
// Whole-page file cache for anonymous GET requests: a hit is served straight
// off disk before index.php ever connects to the database. No TTL -- a
// cached page lives until something invalidates it (admin content changes
// flush the whole cache; see the /admin POST hook in index.php) or an admin
// clears it from Maintenance. Simpler and more predictable than time-based
// staleness for a CMS where "content changed" is a well-defined moment.

if (!function_exists('cache_dir')) {
    function cache_dir(): string
    {
        $dir = BASE_PATH . '/cache/pages';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}

if (!function_exists('cache_key_for_request')) {
    function cache_key_for_request(string $path, string $queryString): string
    {
        return md5($path . '?' . $queryString);
    }
}

if (!function_exists('cache_get')) {
    function cache_get(string $key): ?string
    {
        $file = cache_dir() . '/' . $key . '.html';
        if (!is_file($file)) {
            return null;
        }
        $content = @file_get_contents($file);
        return $content === false ? null : $content;
    }
}

if (!function_exists('cache_put')) {
    function cache_put(string $key, string $html): void
    {
        @file_put_contents(cache_dir() . '/' . $key . '.html', $html);
    }
}

if (!function_exists('cache_clear')) {
    function cache_clear(): int
    {
        $dir = cache_dir();
        $cleared = 0;
        foreach (glob($dir . '/*.html') ?: [] as $file) {
            if (@unlink($file)) {
                $cleared++;
            }
        }
        return $cleared;
    }
}

if (!function_exists('frontend_render_cached')) {
    /** Renders via $renderFn, capturing its output into the page cache under
     * $cacheKey before echoing it. $renderFn is expected to call view_frontend()
     * (which never returns void-usefully -- it echoes directly), so this
     * wraps it in an output buffer rather than expecting a return value. */
    function frontend_render_cached(string $cacheKey, callable $renderFn): void
    {
        ob_start();
        $renderFn();
        $html = (string) ob_get_clean();
        cache_put($cacheKey, $html);
        echo $html;
    }
}
