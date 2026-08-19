<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
require_once __DIR__ . '/core/start.php';

use App\Core\ModuleLoader;
use Core\Installer\Runtime as InstallerRuntime;

function admin_db(): PDO
{
    return db_connect((array) cms_config('database', []));
}

function ensure_path(string $path): string
{
    $path = '/' . trim($path, '/');
    return $path === '//' ? '/' : $path;
}

function normalize_admin_path(string $path): string
{
    return rtrim($path, '/') ?: '/';
}

function setting_value(PDO $pdo, string $key, mixed $default = null): mixed
{
    $stmt = $pdo->prepare('SELECT setting_value FROM ' . jura_table('settings') . ' WHERE setting_key=? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function save_setting(PDO $pdo, string $key, mixed $value, string $group = 'system', string $type = 'string'): void
{
    $pdo->prepare('INSERT INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),setting_type=VALUES(setting_type),group_name=VALUES(group_name)')
        ->execute([$key, (string) $value, $type, $group]);
}

function cms_settings(PDO $pdo): array
{
    $rows = $pdo->query('SELECT setting_key,setting_value FROM ' . jura_table('settings'))->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
    }
    return $settings;
}

const CMS_SCHEMA_VERSION = '4';

function ensure_cms_schema(PDO $pdo): void
{
    static $ensuredThisRequest = false;
    if ($ensuredThisRequest) {
        return;
    }
    // This function used to run its full set of SHOW/CREATE/ALTER/INSERT
    // IGNORE checks on every single request (frontend and admin alike),
    // which is a lot of avoidable round-trips once the schema is already
    // up to date. Short-circuit via a file marker; bump CMS_SCHEMA_VERSION
    // whenever a table/column/default changes below so it re-runs once.
    $marker = BASE_PATH . '/storage/schema-version.txt';
    if (is_file($marker) && trim((string) @file_get_contents($marker)) === CMS_SCHEMA_VERSION) {
        $ensuredThisRequest = true;
        return;
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('locales') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(16) UNIQUE,name VARCHAR(191),native_name VARCHAR(191),is_default TINYINT(1) DEFAULT 0,is_active TINYINT(1) DEFAULT 1,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('form_submissions') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,form_code VARCHAR(120),locale VARCHAR(16) NULL,source_url VARCHAR(255) NULL,name VARCHAR(191) NULL,email VARCHAR(191) NULL,phone VARCHAR(80) NULL,message TEXT NULL,payload_json JSON NULL,status VARCHAR(40) DEFAULT 'new',ip_address VARCHAR(45) NULL,user_agent VARCHAR(255) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('migrations') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,migration VARCHAR(191) UNIQUE,batch INT UNSIGNED DEFAULT 1,executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('settings') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,setting_key VARCHAR(191) UNIQUE,setting_value TEXT NULL,setting_type VARCHAR(40) DEFAULT 'string',group_name VARCHAR(80) DEFAULT 'system',updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        ['settings', 'setting_type', "VARCHAR(40) DEFAULT 'string'"],
        ['settings', 'group_name', "VARCHAR(80) DEFAULT 'system'"],
        ['pages', 'canonical_path', 'VARCHAR(191) NULL'],
        ['pages', 'og_title', 'VARCHAR(191) NULL'],
        ['pages', 'og_description', 'TEXT NULL'],
        ['posts', 'canonical_path', 'VARCHAR(191) NULL'],
        ['posts', 'og_title', 'VARCHAR(191) NULL'],
        ['posts', 'og_description', 'TEXT NULL'],
        ['posts', 'featured_image', 'VARCHAR(255) NULL'],
        ['posts', 'sort_order', 'INT NOT NULL DEFAULT 0'],
        ['redirects', 'notes', 'TEXT NULL'],
        ['redirects', 'hit_count', 'INT UNSIGNED DEFAULT 0'],
        ['redirects', 'last_hit_at', 'TIMESTAMP NULL'],
        ['media_files', 'folder', 'VARCHAR(191) NULL'],
        ['users', 'role', "VARCHAR(40) NOT NULL DEFAULT 'admin'"],
        ['pages', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
        ['pages', 'translation_of', 'INT UNSIGNED NULL'],
        ['posts', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
        ['posts', 'translation_of', 'INT UNSIGNED NULL'],
    ] as [$table, $column, $definition]) {
        $tableName = str_replace('`', '', jura_table($table));
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE " . $pdo->quote($column));
        if (!$stmt->fetch()) {
            try {
                $pdo->exec("ALTER TABLE `{$tableName}` ADD `{$column}` {$definition}");
            } catch (\PDOException $e) {
                // Concurrent requests can both see the column missing and both
                // try to add it; the loser gets "Duplicate column" (42S21) —
                // that just means another request already added it, not an
                // error worth failing the whole request over.
                if ($e->getCode() !== '42S21') {
                    throw $e;
                }
            }
        }
    }
    $defaults = [
        ['site_name', 'Jura CMS', 'system'],
        ['site_url', '', 'system'],
        ['default_locale', 'uk', 'localization'],
        ['default_locale_has_prefix', '0', 'localization'],
        ['active_locales', 'uk,ru,en', 'localization'],
        ['contact_phone', '', 'contacts'],
        ['contact_phone2', '', 'contacts'],
        ['contact_email', '', 'contacts'],
        ['contact_address', '', 'contacts'],
        ['social_facebook', '', 'contacts'],
        ['social_instagram', '', 'contacts'],
        ['google_maps_embed', '', 'integrations'],
        ['gtm_id', '', 'integrations'],
        ['ga4_id', '', 'integrations'],
        ['google_ads_id', '', 'integrations'],
        ['fb_pixel_id', '', 'integrations'],
        ['fb_access_token', '', 'integrations'],
        ['menu_header', 'main', 'system'],
        ['menu_footer', 'main', 'system'],
        ['telegram_bot_token', '', 'notifications'],
        ['telegram_chat_id', '', 'notifications'],
        ['notification_email_to', '', 'notifications'],
        ['thankyou_page', '/thankyou', 'forms'],
        ['blog_per_page', '12', 'system'],
    ];
    foreach ($defaults as [$key, $value, $group]) {
        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?)')
            ->execute([$key, $value, 'string', $group]);
    }
    // default_locale (settings) is the authoritative "what locale is this
    // site in" value — the installer can set it to anything (e.g. 'ru').
    // jura_locales.is_default must always agree with it; seed new rows
    // against it (not a hardcoded 'uk') and reconcile existing rows every
    // time this runs, since a mismatch here means current_locale()
    // resolves a different locale than the content was backfilled onto
    // below, silently emptying every locale-filtered listing (e.g. /blog).
    $defaultLocale = (string) setting_value($pdo, 'default_locale', 'uk');
    foreach ([['uk', 'Ukrainian', 'Українська', 1], ['ru', 'Russian', 'Русский', 2], ['en', 'English', 'English', 3]] as [$code, $name, $native, $sort]) {
        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('locales') . ' (code,name,native_name,is_default,sort_order) VALUES (?,?,?,?,?)')
            ->execute([$code, $name, $native, $code === $defaultLocale ? 1 : 0, $sort]);
    }
    $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET is_default=(code=?)')->execute([$defaultLocale]);

    // Existing pages/posts predate the locale column — backfill them onto
    // the site's default locale rather than leaving it blank, so they keep
    // rendering exactly as before until an admin adds real translations.
    $pdo->prepare('UPDATE ' . jura_table('pages') . " SET locale=? WHERE locale=''")->execute([$defaultLocale]);
    $pdo->prepare('UPDATE ' . jura_table('posts') . " SET locale=? WHERE locale=''")->execute([$defaultLocale]);

    // Any admin user works as the author here -- this just needs to exist,
    // not belong to anyone in particular.
    $anyAdminId = (int) $pdo->query('SELECT id FROM ' . jura_table('users') . ' ORDER BY id LIMIT 1')->fetchColumn();
    if ($anyAdminId) {
        jura_seed_thankyou_page($pdo, $anyAdminId);
    }

    $dir = dirname($marker);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($marker, CMS_SCHEMA_VERSION);
    $ensuredThisRequest = true;
}

/** Active locale codes ordered for display, and the default locale code.
 * jura_locales is the source of truth; the default_locale/active_locales
 * settings are kept in sync (by the /admin/locales page) purely as a
 * cheap fallback in case that table is ever empty. */
function active_locales(PDO $pdo): array
{
    try {
        $rows = $pdo->query('SELECT code,is_default FROM ' . jura_table('locales') . ' WHERE is_active=1 ORDER BY sort_order,id')->fetchAll();
    } catch (\Throwable) {
        $rows = [];
    }
    if ($rows) {
        $codes = array_column($rows, 'code');
        $defaultRow = current(array_filter($rows, fn($r) => (int) $r['is_default'] === 1));
        $default = $defaultRow ? (string) $defaultRow['code'] : (string) $codes[0];
        return [$codes, $default];
    }
    $settings = cms_settings($pdo);
    $default = (string) ($settings['default_locale'] ?? 'uk');
    $active = array_values(array_filter(array_map('trim', explode(',', (string) ($settings['active_locales'] ?? $default)))));
    return [$active ?: [$default], $default];
}

function current_locale(PDO $pdo, string $path): array
{
    [$active, $default] = active_locales($pdo);
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $prefix = $segments[0] ?? '';
    if (in_array($prefix, $active, true)) {
        array_shift($segments);
        return [$prefix, ensure_path(implode('/', $segments)), true];
    }
    return [$default, $path, false];
}

function frontend_route(PDO $pdo, string $path): ?array
{
    [$locale, $routePath] = current_locale($pdo, $path);
    $table = jura_table('routes');
    foreach ([$path, $routePath] as $candidate) {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE path=:p AND status='active' LIMIT 1");
        $stmt->execute(['p' => $candidate]);
        $route = $stmt->fetch();
        if ($route) {
            $route['locale'] = $locale;
            return $route;
        }
    }
    return null;
}

/**
 * All translations of a page/post, keyed by locale code — including the
 * row itself. Used to render a language switcher and to drive the admin
 * "Translations" panel. $table is 'pages' or 'posts'; $row must have
 * id, locale, translation_of.
 */
function entity_translations(PDO $pdo, string $table, array $row): array
{
    $rootId = (int) ($row['translation_of'] ?: $row['id']);
    $entityType = rtrim($table, 's'); // 'pages' -> 'page', 'posts' -> 'post'
    $stmt = $pdo->prepare(
        'SELECT e.id,e.locale,e.title,r.path route_path FROM ' . jura_table($table) . ' e '
        . 'LEFT JOIN ' . jura_table('routes') . " r ON r.entity_type=? AND r.entity_id=e.id "
        . 'WHERE e.id=? OR e.translation_of=?'
    );
    $stmt->execute([$entityType, $rootId, $rootId]);
    $out = [];
    foreach ($stmt->fetchAll() as $t) {
        $out[(string) $t['locale']] = $t;
    }
    return $out;
}

/** The default-locale-most page/post id a translation belongs to (itself if it has no translation_of). */
function translation_root_id(array $row): int
{
    return (int) ($row['translation_of'] ?: $row['id']);
}

function find_redirect(PDO $pdo, string $path): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('redirects') . ' WHERE source_path=? AND is_active=1 LIMIT 1');
    $stmt->execute([$path]);
    $redirect = $stmt->fetch();
    if (!$redirect) {
        return null;
    }
    $pdo->prepare('UPDATE ' . jura_table('redirects') . ' SET hit_count=COALESCE(hit_count,0)+1,last_hit_at=NOW() WHERE id=?')->execute([(int) $redirect['id']]);
    return $redirect;
}


function notify_lead(PDO $pdo, string $formCode, array $payload): void
{
    $settings = cms_settings($pdo);
    $formLabels = ['contact' => 'Контакти'];
    $formLabel  = $formLabels[$formCode] ?? $formCode;
    $name       = trim((string) ($payload['name'] ?? ''));
    $subjectText = 'Заявка з сайту — ' . $formLabel . ($name !== '' ? ' — ' . $name : '');

    $fieldLabels = [
        'name'    => "Ім'я",
        'phone'   => 'Телефон',
        'email'   => 'Email',
        'message' => 'Повідомлення',
        'locale'  => null,
    ];
    $message = "Нова заявка з сайту — {$formLabel}\n\n";
    foreach ($payload as $key => $value) {
        if (!is_scalar($value)) continue;
        $label = $fieldLabels[$key] ?? null;
        if ($label === null) continue;
        $message .= $label . ': ' . (string) $value . "\n";
    }

    $emailTo = trim((string) ($settings['notification_email_to'] ?? ''));
    if ($emailTo !== '' && function_exists('mail')) {
        $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . ($settings['site_name'] ?? 'Jura CMS') . ' <' . $emailTo . '>',
            'X-Mailer: JuraCMS',
        ]);
        @mail($emailTo, $subject, $message, $headers);
    }
    $botToken = trim((string) ($settings['telegram_bot_token'] ?? ''));
    $chatId = trim((string) ($settings['telegram_chat_id'] ?? ''));
    if ($botToken !== '' && $chatId !== '') {
        $url = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/sendMessage';
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query(['chat_id' => $chatId, 'text' => $message]), 'timeout' => 3]]);
        @file_get_contents($url, false, $context);
    }
}

function admin_stats(PDO $pdo): array
{
    $stats = [];
    foreach (['pages', 'posts', 'media_files', 'users', 'redirects', 'menu_items'] as $table) {
        try { $stats[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table($table))->fetchColumn(); } catch (\Throwable $e) { $stats[$table] = 0; }
    }
    try { $stats['leads'] = (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table('form_submissions'))->fetchColumn(); } catch (\Throwable $e) { $stats['leads'] = 0; }
    return array_merge($stats, ModuleLoader::hookCollect('admin_stats', $pdo));
}

function save_route(PDO $pdo, string $path, string $entityType, int $entityId): void
{
    $path = ensure_path($path);
    $pdo->prepare('INSERT INTO ' . jura_table('routes') . ' (path,entity_type,entity_id,status) VALUES (?,?,?,"active") ON DUPLICATE KEY UPDATE entity_type=VALUES(entity_type),entity_id=VALUES(entity_id),status="active"')
        ->execute([$path, $entityType, $entityId]);
}

function frontend_menu_items(PDO $pdo, string $code): array
{
    try {
        $stmt = $pdo->prepare('SELECT i.* FROM ' . jura_table('menu_items') . ' i JOIN ' . jura_table('menus') . " m ON m.id=i.menu_id WHERE m.code=? AND i.status='active' ORDER BY i.sort_order,i.id");
        $stmt->execute([$code]);
        return $stmt->fetchAll();
    } catch (\Throwable) {
        return [];
    }
}

function uploaded_media_path(array $file): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $name = (string) ($file['name'] ?? 'file');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $safeBase = slugify(pathinfo($name, PATHINFO_FILENAME)) ?: 'file';
    $filename = $safeBase . '-' . date('His') . '-' . bin2hex(random_bytes(3)) . ($ext ? '.' . $ext : '');
    $folder = 'uploads/' . date('Y/m');
    $absoluteFolder = BASE_PATH . '/' . $folder;
    if (!is_dir($absoluteFolder)) {
        mkdir($absoluteFolder, 0775, true);
    }
    $absolute = $absoluteFolder . '/' . $filename;
    if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
        return null;
    }
    return [$folder . '/' . $filename, $filename, $name, $absolute, $folder];
}

$path = normalize_admin_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Whole-page cache for anonymous frontend GETs -- served straight off disk,
// before even connecting to the database, when there's a hit.
$frontendCacheKey = null;
if ($method === 'GET' && !str_starts_with($path, '/admin') && !str_starts_with($path, '/install') && !str_starts_with($path, '/forms')) {
    $frontendCacheKey = cache_key_for_request($path, (string) ($_SERVER['QUERY_STRING'] ?? ''));
    $cached = cache_get($frontendCacheKey);
    if ($cached !== null) {
        echo $cached;
        exit;
    }
}

if (!InstallerRuntime::isInstalled() && !in_array($path, ['/install', '/install/'], true)) {
    redirect('/install/');
}
if ($method !== 'GET' && !str_starts_with($path, '/admin') && !str_starts_with($path, '/forms') && !str_starts_with($path, '/install')) {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}
if ($path === '/admin/page') {
    redirect('/admin/pages');
}

switch (true) {
    case $path === '/admin/login':
        if (!InstallerRuntime::isInstalled()) {
            redirect('/install/');
        }
        $dbConfig = (array) cms_config('database', []);
        if ($method === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            try {
                $pdo = db_connect($dbConfig);
                ensure_cms_schema($pdo);
                $stmt = $pdo->prepare('SELECT id,email,password_hash,status FROM ' . jura_table('users') . ' WHERE email=:email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
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
    case $path === '/admin/logout':
        if ($method !== 'POST') {
            http_response_code(405);
            exit;
        }
        unset($_SESSION['admin_user_id'], $_SESSION['admin_user_email']);
        redirect('/admin/login');
}

if (str_starts_with($path, '/admin')) {
    admin_require_auth();
    $pdo = admin_db();
    // Any admin write can change what the frontend should show -- flush the
    // whole page cache rather than trying to track which cached paths a
    // given save affects. Cheap (a POST is rare next to page views) and
    // never leaves stale content behind.
    if ($method === 'POST') {
        cache_clear();
    }
    ensure_cms_schema($pdo);
    ModuleLoader::ensureTable($pdo);
    ModuleLoader::autoMigrate($pdo);
    ModuleLoader::loadInstalled($pdo);
    ModuleLoader::hookEach('ensure_schema', $pdo);

    if ($path === '/admin') {
        view_admin('dashboard', ['title' => 'Дашборд', 'stats' => admin_stats($pdo)]);
        exit;
    }

    if ($path === '/admin/settings') {
        if ($method === 'POST') {
            foreach ($_POST['settings'] ?? [] as $key => $value) {
                $group = str_contains((string) $key, 'locale') ? 'localization' : 'system';
                if (str_starts_with((string) $key, 'contact_') || str_starts_with((string) $key, 'social_') || (string) $key === 'contact_address' || (string) $key === 'show_phone') {
                    $group = 'contacts';
                }
                if (in_array((string) $key, ['google_maps_embed', 'gtm_id', 'ga4_id', 'google_ads_id', 'fb_pixel_id', 'fb_access_token'], true)) {
                    $group = 'integrations';
                }
                if (str_starts_with((string) $key, 'telegram_') || (string) $key === 'notification_email_to') {
                    $group = 'notifications';
                }
                if ((string) $key === 'thankyou_page') {
                    $group = 'forms';
                }
                if ((string) $key === 'blog_per_page') {
                    $group = 'system';
                }
                if (in_array((string) $key, ['admin_jura_theme', 'admin_dark_mode'], true)) {
                    $group = 'system';
                }
                // menu_* keys
                if (str_starts_with((string) $key, 'menu_') || in_array((string) $key, ['site_name', 'site_url'], true)) {
                    $group = 'system';
                }
                save_setting($pdo, (string) $key, is_array($value) ? implode(',', $value) : $value, $group);
            }
            // Checkboxes not sent when unchecked — save explicit 0
            if (!isset($_POST['settings']['admin_dark_mode'])) {
                save_setting($pdo, 'admin_dark_mode', '0', 'system');
            }
            if (!isset($_POST['settings']['show_phone'])) {
                save_setting($pdo, 'show_phone', '0', 'contacts');
            }
            session_flash('success', 'Settings saved.');
            redirect('/admin/settings');
        }
        view_admin('settings', ['title' => 'Налаштування', 'settings' => cms_settings($pdo), 'success' => session_flash('success')]);
        exit;
    }

    if ($path === '/admin/update-library' && $method === 'POST') {
        $lib = $_POST['lib'] ?? '';
        $result = '';
        $tmpDir = sys_get_temp_dir() . '/jura-lib-update-' . $lib . '-' . time();
        if (!exec_available()) {
            $result = 'Функція exec вимкнена на цьому хостингу — оновлення бібліотек через адмінку недоступне.';
        } elseif ($lib === 'juraui') {
            $cloneCmd = 'git clone --depth 1 https://github.com/medvestnik/juraui.git ' . escapeshellarg($tmpDir) . ' 2>&1';
            exec($cloneCmd, $out, $code);
            if ($code === 0) {
                $src = $tmpDir . '/assets/css/jura-ui.css';
                $dst = BASE_PATH . '/public/assets/jura-ui/jura-ui.css';
                if (file_exists($src)) {
                    copy($src, $dst);
                    $result = 'Jura UI CSS оновлено успішно.';
                } else {
                    $result = 'Помилка: файл jura-ui.css не знайдено в репозиторії.';
                }
                // Copy JS components
                $jsSrc = [
                    $tmpDir . '/src/js/core/theme.js',
                    $tmpDir . '/src/js/components/dropdown.js',
                    $tmpDir . '/src/js/components/modal.js',
                    $tmpDir . '/src/js/components/tabs.js',
                ];
                $jsContent = '';
                foreach ($jsSrc as $f) {
                    if (file_exists($f)) {
                        $jsContent .= preg_replace('/^export function /m', 'function ', file_get_contents($f)) . "\n";
                    }
                }
                if ($jsContent) {
                    $jsContent .= "\ndocument.addEventListener('DOMContentLoaded',function(){initTheme&&initTheme();initDropdowns&&initDropdowns();initModals&&initModals();initTabs&&initTabs();});\n";
                    file_put_contents(BASE_PATH . '/public/assets/jura-ui/jura-ui.js', $jsContent);
                    $result .= ' JS оновлено.';
                }
                exec('rm -rf ' . escapeshellarg($tmpDir));
            } else {
                $result = 'Помилка git clone: ' . implode(' ', $out);
            }
        } elseif ($lib === 'simple-js-editor') {
            $cloneCmd = 'git clone --depth 1 https://github.com/medvestnik/simple-js-editor.git ' . escapeshellarg($tmpDir) . ' 2>&1';
            exec($cloneCmd, $out, $code);
            if ($code === 0) {
                $buildOut = [];
                exec('cd ' . escapeshellarg($tmpDir) . ' && npm install --silent 2>&1 && npm run build 2>&1', $buildOut, $buildCode);
                if ($buildCode === 0 && file_exists($tmpDir . '/dist/simple-js-editor.umd.js')) {
                    copy($tmpDir . '/dist/simple-js-editor.umd.js', BASE_PATH . '/public/assets/admin/editor/simple-js-editor.js');
                    copy($tmpDir . '/dist/style.css', BASE_PATH . '/public/assets/admin/editor/simple-js-editor.css');
                    $result = 'Simple JS Editor оновлено успішно.';
                } else {
                    $result = 'Помилка збірки: ' . implode(' ', array_slice($buildOut, -5));
                }
                exec('rm -rf ' . escapeshellarg($tmpDir));
            } else {
                $result = 'Помилка git clone: ' . implode(' ', $out);
            }
        } else {
            $result = 'Невідома бібліотека.';
        }
        view_admin('settings', ['title' => 'Налаштування', 'settings' => cms_settings($pdo), 'success' => session_flash('success'), 'lib_update_result' => $result]);
        exit;
    }

    if ($path === '/admin/pages' && $method === 'GET') {
        $pages = $pdo->query('SELECT p.*,r.path route_path FROM ' . jura_table('pages') . ' p LEFT JOIN ' . jura_table('routes') . " r ON r.entity_type='page' AND r.entity_id=p.id ORDER BY p.sort_order,p.id")->fetchAll();
        view_admin('pages', ['title' => 'Сторінки', 'pages' => $pages, 'edit' => null]);
        exit;
    }
    if ($path === '/admin/pages/create' && $method === 'GET') {
        view_admin('pages', ['title' => 'Додати сторінку', 'edit' => []]);
        exit;
    }
    if ($path === '/admin/pages' && $method === 'POST') {
        $status = (string) ($_POST['status'] ?? 'draft');
        $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
        $route = ensure_path((string) ($_POST['route_path'] ?? ('/' . $slug)));
        if ($route === '/home') {
            $route = '/';
        }
        [, $defaultLocale] = active_locales($pdo);
        $stmt = $pdo->prepare('INSERT INTO ' . jura_table('pages') . ' (author_id,title,slug,content,excerpt,status,template,meta_title,meta_description,meta_keywords,canonical_path,og_title,og_description,sort_order,locale,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CASE WHEN ?="published" THEN NOW() ELSE NULL END)');
        $stmt->execute([(int) $_SESSION['admin_user_id'], $_POST['title'], $slug, $_POST['content'] ?? '', $_POST['excerpt'] ?? '', $status, $_POST['template'] ?? 'page', $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', $_POST['meta_keywords'] ?? '', $_POST['canonical_path'] ?? '', $_POST['og_title'] ?? '', $_POST['og_description'] ?? '', (int) ($_POST['sort_order'] ?? 0), $defaultLocale, $status]);
        $id = (int) $pdo->lastInsertId();
        save_route($pdo, $route, 'page', $id);
        redirect(isset($_POST['_close']) ? '/admin/pages' : '/admin/pages/' . $id . '/edit');
    }
    if (preg_match('#^/admin/pages/(\d+)/edit$#', $path, $matches) && $method === 'GET') {
        $stmt = $pdo->prepare('SELECT p.*,r.path route_path FROM ' . jura_table('pages') . ' p LEFT JOIN ' . jura_table('routes') . " r ON r.entity_type='page' AND r.entity_id=p.id WHERE p.id=?");
        $stmt->execute([(int) $matches[1]]);
        $editPage = $stmt->fetch() ?: [];
        [$activeCodes] = $editPage ? active_locales($pdo) : [[]];
        $localeRows = $editPage ? $pdo->query('SELECT code,name,native_name FROM ' . jura_table('locales') . ' ORDER BY sort_order,id')->fetchAll() : [];
        view_admin('pages', [
            'title' => 'Редагувати сторінку',
            'edit' => $editPage,
            'translations' => $editPage ? entity_translations($pdo, 'pages', $editPage) : [],
            'all_locales' => $localeRows,
        ]);
        exit;
    }
    if (preg_match('#^/admin/pages/(\d+)/translate$#', $path, $matches) && $method === 'POST') {
        $sourceId = (int) $matches[1];
        $locale = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['locale'] ?? '')));
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('pages') . ' WHERE id=?');
        $stmt->execute([$sourceId]);
        $source = $stmt->fetch();
        if ($source && $locale !== '') {
            $rootId = translation_root_id($source);
            $existing = $pdo->prepare('SELECT id FROM ' . jura_table('pages') . ' WHERE (id=? OR translation_of=?) AND locale=?');
            $existing->execute([$rootId, $rootId, $locale]);
            $newId = (int) $existing->fetchColumn();
            if (!$newId) {
                $ins = $pdo->prepare('INSERT INTO ' . jura_table('pages') . ' (author_id,title,slug,content,excerpt,status,template,meta_title,meta_description,meta_keywords,sort_order,locale,translation_of) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $ins->execute([(int) $_SESSION['admin_user_id'], $source['title'], $source['slug'], '', $source['excerpt'], 'draft', $source['template'], '', '', '', $source['sort_order'], $locale, $rootId]);
                $newId = (int) $pdo->lastInsertId();
                // The home page's translation gets its locale's root URL
                // (/en) rather than /en/home -- home is always "/" in the
                // default locale, so its translations follow the same rule.
                $routePath = $source['template'] === 'home' ? '/' . $locale : '/' . $locale . '/' . $source['slug'];
                save_route($pdo, $routePath, 'page', $newId);
            }
            redirect('/admin/pages/' . $newId . '/edit');
        }
        redirect('/admin/pages/' . $sourceId . '/edit');
    }
    if (preg_match('#^/admin/pages/(\d+)$#', $path, $matches) && $method === 'POST') {
        $id = (int) $matches[1];
        $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
        $route = ensure_path((string) ($_POST['route_path'] ?? ('/' . $slug)));
        $pdo->prepare('UPDATE ' . jura_table('pages') . ' SET title=?,slug=?,content=?,excerpt=?,status=?,template=?,meta_title=?,meta_description=?,meta_keywords=?,canonical_path=?,og_title=?,og_description=?,sort_order=?,updated_at=NOW(),published_at=CASE WHEN ?="published" AND published_at IS NULL THEN NOW() ELSE published_at END WHERE id=?')
            ->execute([$_POST['title'], $slug, $_POST['content'] ?? '', $_POST['excerpt'] ?? '', $_POST['status'] ?? 'draft', $_POST['template'] ?? 'page', $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', $_POST['meta_keywords'] ?? '', $_POST['canonical_path'] ?? '', $_POST['og_title'] ?? '', $_POST['og_description'] ?? '', (int) ($_POST['sort_order'] ?? 0), $_POST['status'] ?? 'draft', $id]);
        $pdo->prepare('DELETE FROM ' . jura_table('routes') . " WHERE entity_type='page' AND entity_id=?")->execute([$id]);
        save_route($pdo, $route, 'page', $id);
        redirect(isset($_POST['_close']) ? '/admin/pages' : '/admin/pages/' . $id . '/edit');
    }
    if (preg_match('#^/admin/pages/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        $id = (int) $matches[1];
        $pdo->prepare('DELETE FROM ' . jura_table('routes') . " WHERE entity_type='page' AND entity_id=?")->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('pages') . ' WHERE id=?')->execute([$id]);
        redirect('/admin/pages');
    }

    if ($path === '/admin/media') {
        $media = $pdo->query('SELECT * FROM ' . jura_table('media_files') . ' ORDER BY id DESC')->fetchAll();
        view_admin('media', ['title' => 'Медіа', 'media' => $media, 'success' => session_flash('success'), 'error' => session_flash('error')]);
        exit;
    }
    if ($path === '/admin/media/upload' && $method === 'POST') {
        $stored = uploaded_media_path($_FILES['file'] ?? []);
        if (!$stored) {
            session_flash('error', 'Upload failed.');
            redirect('/admin/media');
        }
        [$relative, $filename, $original, $absolute, $folder] = $stored;
        $mime = function_exists('mime_content_type') ? (string) mime_content_type($absolute) : 'application/octet-stream';
        $size = (int) filesize($absolute);
        [$width, $height] = str_starts_with($mime, 'image/') ? (getimagesize($absolute) ?: [null, null]) : [null, null];
        $pdo->prepare('INSERT INTO ' . jura_table('media_files') . ' (user_id,disk,path,filename,original_name,mime_type,size,width,height,alt,title,folder) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([(int) $_SESSION['admin_user_id'], 'public', $relative, $filename, $original, $mime, $size, $width, $height, $_POST['alt'] ?? '', $_POST['title'] ?? '', $folder]);
        session_flash('success', 'File uploaded.');
        redirect('/admin/media');
    }

    if ($path === '/admin/menus') {
        if ($method === 'POST') {
            if (($_POST['action'] ?? '') === 'create_menu') {
                $pdo->prepare('INSERT INTO ' . jura_table('menus') . ' (code,name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)')->execute([slugify((string) $_POST['code']), $_POST['name']]);
            }
            if (($_POST['action'] ?? '') === 'add_item') {
                $pdo->prepare('INSERT INTO ' . jura_table('menu_items') . ' (menu_id,parent_id,title,url,target,sort_order,status) VALUES (?,?,?,?,?,?,?)')->execute([(int) $_POST['menu_id'], ($_POST['parent_id'] ?? '') !== '' ? (int) $_POST['parent_id'] : null, $_POST['title'], $_POST['url'], $_POST['target'] ?? '_self', (int) ($_POST['sort_order'] ?? 0), $_POST['status'] ?? 'active']);
            }
            if (($_POST['action'] ?? '') === 'delete_item') {
                $pdo->prepare('DELETE FROM ' . jura_table('menu_items') . ' WHERE id=?')->execute([(int) $_POST['id']]);
            }
            if (($_POST['action'] ?? '') === 'delete_menu') {
                $mid = (int) $_POST['id'];
                $pdo->prepare('DELETE FROM ' . jura_table('menu_items') . ' WHERE menu_id=?')->execute([$mid]);
                $pdo->prepare('DELETE FROM ' . jura_table('menus') . ' WHERE id=?')->execute([$mid]);
            }
            if (($_POST['action'] ?? '') === 'rename_menu') {
                $pdo->prepare('UPDATE ' . jura_table('menus') . ' SET name=? WHERE id=?')->execute([$_POST['name'], (int) $_POST['id']]);
            }
            if (($_POST['action'] ?? '') === 'edit_item') {
                $pdo->prepare('UPDATE ' . jura_table('menu_items') . ' SET title=?,url=?,sort_order=? WHERE id=?')
                    ->execute([$_POST['title'], $_POST['url'], (int)($_POST['sort_order'] ?? 0), (int)$_POST['id']]);
            }
            redirect('/admin/menus');
        }
        $menuPages = $pdo->query('SELECT id,title,slug,template FROM ' . jura_table('pages') . " WHERE status='published' ORDER BY title")->fetchAll();
        $s = cms_settings($pdo);
        view_admin('menus', ['title' => 'Меню', 'menus' => $pdo->query('SELECT * FROM ' . jura_table('menus') . ' ORDER BY name')->fetchAll(), 'items' => $pdo->query('SELECT i.*,m.name menu_name,m.code menu_code FROM ' . jura_table('menu_items') . ' i JOIN ' . jura_table('menus') . ' m ON m.id=i.menu_id ORDER BY m.name,i.sort_order,i.id')->fetchAll(), 'menu_pages' => $menuPages, 'menu_header' => $s['menu_header'] ?? 'main', 'menu_footer' => $s['menu_footer'] ?? 'main']);
        exit;
    }

    if ($path === '/admin/redirects') {
        if ($method === 'POST') {
            if (($_POST['action'] ?? '') === 'delete') {
                $pdo->prepare('DELETE FROM ' . jura_table('redirects') . ' WHERE id=?')->execute([(int) $_POST['id']]);
            } else {
                $source = ensure_path((string) $_POST['source_path']);
                $target = ensure_path((string) $_POST['target_path']);
                $pdo->prepare('INSERT INTO ' . jura_table('redirects') . ' (source_path,target_path,status_code,is_active,notes) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE target_path=VALUES(target_path),status_code=VALUES(status_code),is_active=VALUES(is_active),notes=VALUES(notes)')
                    ->execute([$source, $target, (int) ($_POST['status_code'] ?? 301), isset($_POST['is_active']) ? 1 : 0, $_POST['notes'] ?? '']);
            }
            redirect('/admin/redirects');
        }
        view_admin('redirects', ['title' => 'Редіректи', 'redirects' => $pdo->query('SELECT * FROM ' . jura_table('redirects') . ' ORDER BY id DESC')->fetchAll()]);
        exit;
    }

    if ($path === '/admin/modules') {
        if ($method === 'POST') {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug'] ?? ''));
            if ($slug && ($_POST['action'] ?? '') === 'install') {
                ModuleLoader::install($slug, $pdo);
            } elseif ($slug && ($_POST['action'] ?? '') === 'uninstall') {
                ModuleLoader::uninstall($slug, $pdo);
            } elseif ($slug && ($_POST['action'] ?? '') === 'enable') {
                ModuleLoader::toggle($slug, true, $pdo);
            } elseif ($slug && ($_POST['action'] ?? '') === 'disable') {
                ModuleLoader::toggle($slug, false, $pdo);
            }
            redirect('/admin/modules');
        }
        view_admin('modules', ['title' => 'Модулі', 'modules' => ModuleLoader::available($pdo)]);
        exit;
    }

    if ($path === '/admin/locales') {
        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create') {
                $code = strtolower(trim((string) ($_POST['code'] ?? '')));
                if ($code !== '') {
                    $pdo->prepare('INSERT IGNORE INTO ' . jura_table('locales') . ' (code,name,native_name,sort_order) VALUES (?,?,?,?)')
                        ->execute([$code, trim((string) ($_POST['name'] ?? '')), trim((string) ($_POST['native_name'] ?? '')), (int) ($_POST['sort_order'] ?? 0)]);
                }
            } elseif ($action === 'update') {
                $id = (int) ($_POST['id'] ?? 0);
                $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET name=?,native_name=?,sort_order=?,is_active=? WHERE id=?')
                    ->execute([trim((string) ($_POST['name'] ?? '')), trim((string) ($_POST['native_name'] ?? '')), (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, $id]);
            } elseif ($action === 'set_default') {
                $id = (int) ($_POST['id'] ?? 0);
                $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET is_default=0')->execute();
                $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET is_default=1,is_active=1 WHERE id=?')->execute([$id]);
                $code = $pdo->prepare('SELECT code FROM ' . jura_table('locales') . ' WHERE id=?');
                $code->execute([$id]);
                if ($c = $code->fetchColumn()) {
                    save_setting($pdo, 'default_locale', (string) $c, 'localization');
                }
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                $row = $pdo->prepare('SELECT is_default FROM ' . jura_table('locales') . ' WHERE id=?');
                $row->execute([$id]);
                if ((int) $row->fetchColumn() !== 1) {
                    $pdo->prepare('DELETE FROM ' . jura_table('locales') . ' WHERE id=?')->execute([$id]);
                } else {
                    session_flash('error', 'Не можна видалити мову за замовчуванням.');
                }
            }
            [$activeCodes] = active_locales($pdo);
            save_setting($pdo, 'active_locales', implode(',', $activeCodes), 'localization');
            redirect('/admin/locales');
        }
        $locales = $pdo->query('SELECT * FROM ' . jura_table('locales') . ' ORDER BY sort_order,id')->fetchAll();
        view_admin('locales', ['title' => 'Мови сайту', 'locales' => $locales, 'flash_error' => session_flash('error')]);
        exit;
    }

    if (ModuleLoader::hookFirst('handle_admin', $path, $method, $pdo)) {
        exit;
    }

    if ($path === '/admin/themes' || preg_match('#^/admin/themes/([a-z0-9_-]+)$#', $path, $_tmatch)) {
        $themeSlug = $_tmatch[1] ?? null;

        if ($method === 'POST' && !$themeSlug) {
            $activateSlug = preg_replace('/[^a-z0-9_-]/', '', $_POST['slug'] ?? '');
            if ($activateSlug && ($_POST['action'] ?? '') === 'activate') {
                // Update config/ui.php
                $cfgFile = BASE_PATH . '/config/ui.php';
                $cfgContent = file_get_contents($cfgFile);
                $cfgContent = preg_replace(
                    "/'frontend_theme'\s*=>\s*'[^']*'/",
                    "'frontend_theme' => '" . addslashes($activateSlug) . "'",
                    $cfgContent
                );
                file_put_contents($cfgFile, $cfgContent);
            }
            redirect('/admin/themes');
        }

        // Scan themes/frontend/ for available themes
        $themeBase = BASE_PATH . '/themes/frontend';
        $activeTheme = (string) config_value('ui.frontend_theme', 'default');
        $allThemes = [];
        foreach (glob($themeBase . '/*/theme.json') ?: [] as $manifestFile) {
            $data = json_decode(file_get_contents($manifestFile), true);
            if (is_array($data) && !empty($data['slug'])) {
                $dir = dirname($manifestFile);
                $data['active'] = ($data['slug'] === $activeTheme);
                $data['path']   = $dir;
                $allThemes[]    = $data;
            }
        }

        if ($themeSlug) {
            // Detail page: show info + file tree
            $theme = null;
            foreach ($allThemes as $t) { if ($t['slug'] === $themeSlug) { $theme = $t; break; } }
            if (!$theme) { redirect('/admin/themes'); }

            // Build file tree relative to theme root
            $files = [];
            $baseLen = strlen($theme['path']) + 1;
            $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme['path'], FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if ($file->isFile()) {
                    $rel = substr($file->getPathname(), $baseLen);
                    if (!str_starts_with($rel, '.git')) $files[] = $rel;
                }
            }
            sort($files);

            view_admin('theme-detail', ['title' => 'Тема: ' . ($theme['name'] ?? $themeSlug), 'theme' => $theme, 'files' => $files]);
        } else {
            view_admin('themes', ['title' => 'Шаблони', 'themes' => $allThemes, 'active_theme' => $activeTheme]);
        }
        exit;
    }

    if ($path === '/admin/maintenance') {
        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            $msg = '';
            try {
                if ($action === 'fix_duplicates') {
                    // Keep the page with the lowest id for each slug, delete duplicates without routes
                    $pages = $pdo->query('SELECT id, slug FROM ' . jura_table('pages') . ' ORDER BY id')->fetchAll();
                    $seen = []; $deleted = 0;
                    foreach ($pages as $p) {
                        if (isset($seen[$p['slug']])) {
                            // Check if it has a route
                            $hasRoute = $pdo->prepare('SELECT id FROM ' . jura_table('routes') . " WHERE entity_type='page' AND entity_id=? LIMIT 1");
                            $hasRoute->execute([(int)$p['id']]);
                            if (!$hasRoute->fetch()) {
                                $pdo->prepare('DELETE FROM ' . jura_table('pages') . ' WHERE id=?')->execute([(int)$p['id']]);
                                $deleted++;
                            }
                        } else {
                            $seen[$p['slug']] = $p['id'];
                        }
                    }
                    $msg = "Видалено дублів: {$deleted}";
                }
                if ($action === 'fix_templates') {
                    $pdo->prepare('UPDATE ' . jura_table('pages') . " SET template='home' WHERE slug='home'")->execute();
                    $pdo->prepare('UPDATE ' . jura_table('pages') . " SET template='contacts' WHERE slug='contacts'")->execute();
                    $msg = 'Шаблони оновлено: home → home, contacts → contacts';
                }
                if ($action === 'rebuild_routes') {
                    $pageRoutes = ['home' => '/', 'about' => '/about', 'contacts' => '/contacts', 'blog' => '/blog'];
                    foreach ($pageRoutes as $slug => $routePath) {
                        $stmt = $pdo->prepare('SELECT id FROM ' . jura_table('pages') . ' WHERE slug=? ORDER BY id LIMIT 1');
                        $stmt->execute([$slug]);
                        $page = $stmt->fetch();
                        if ($page) {
                            $pdo->prepare('INSERT INTO ' . jura_table('routes') . ' (path,entity_type,entity_id,status) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE entity_type=VALUES(entity_type),entity_id=VALUES(entity_id),status=\'active\'')
                                ->execute([$routePath, 'page', (int)$page['id'], 'active']);
                        }
                    }
                    $msg = 'Роути відновлено';
                }
                if ($action === 'rebuild_menu') {
                    $menuStmt = $pdo->query('SELECT id FROM ' . jura_table('menus') . " WHERE code='main' LIMIT 1");
                    $menu = $menuStmt->fetch();
                    if (!$menu) {
                        $pdo->prepare('INSERT INTO ' . jura_table('menus') . ' (code,name) VALUES (?,?)')->execute(['main', 'Main']);
                        $menuId = (int) $pdo->lastInsertId();
                    } else {
                        $menuId = (int) $menu['id'];
                    }
                    $pdo->prepare('DELETE FROM ' . jura_table('menu_items') . ' WHERE menu_id=?')->execute([$menuId]);
                    $items = [['Головна', '/'], ['Про нас', '/about'], ['Блог', '/blog'], ['Контакти', '/contacts']];
                    foreach ($items as $i => [$label, $url]) {
                        $pdo->prepare('INSERT INTO ' . jura_table('menu_items') . ' (menu_id,title,url,sort_order,status) VALUES (?,?,?,?,?)')->execute([$menuId, $label, $url, $i + 1, 'active']);
                    }
                    $msg = 'Меню оновлено: ' . count($items) . ' пунктів';
                }
                if ($action === 'refresh_demo_content') {
                    $settings = cms_settings($pdo);
                    $siteName = $settings['site_name'] ?? 'Jura CMS';
                    $homeContent = '<p>' . e($siteName) . ' &mdash; це легка система керування сайтом із класичною установкою в корінь хостингу та сучасною адмін-панеллю. Створюйте сторінки, ведіть блог, керуйте медіатекою та меню &mdash; все з коробки.</p><p>Ця сторінка, як і сторінки &laquo;Про нас&raquo; та &laquo;Контакти&raquo;, &mdash; демонстраційний контент. Відредагуйте або видаліть його в розділі <strong>Сторінки</strong> адмін-панелі.</p>';
                    $aboutContent = '<p>' . e($siteName) . ' працює на Jura CMS &mdash; системі керування сайтом, що поєднує простоту класичних движків із зручністю адмін-панелі нового покоління.</p><p>У цьому розділі зазвичай розповідають історію компанії, місію та команду. Замініть цей текст власним описом у розділі <strong>Сторінки</strong>.</p><div class="feature-grid" style="margin-top:1.75rem"><div class="feature-card"><div class="feature-card__icon">🎯</div><h3>Місія</h3><p>Дати простий та зрозумілий інструмент для створення й розвитку сайту.</p></div><div class="feature-card"><div class="feature-card__icon">⚡</div><h3>Швидкість</h3><p>Мінімум залежностей, встановлення в корінь хостингу за кілька хвилин.</p></div><div class="feature-card"><div class="feature-card__icon">🤝</div><h3>Підтримка</h3><p>Оновлення та документація для впевненого старту й розвитку проєкту.</p></div></div>';
                    $contactsContent = '<p>Залишились питання? Напишіть нам &mdash; форма нижче надсилає повідомлення прямо на пошту адміністратора сайту.</p>';
                    $pdo->prepare('UPDATE ' . jura_table('pages') . " SET content=? WHERE slug='home' ORDER BY id LIMIT 1")->execute([$homeContent]);
                    $pdo->prepare('UPDATE ' . jura_table('pages') . " SET content=? WHERE slug='about' ORDER BY id LIMIT 1")->execute([$aboutContent]);
                    $pdo->prepare('UPDATE ' . jura_table('pages') . " SET content=? WHERE slug='contacts' ORDER BY id LIMIT 1")->execute([$contactsContent]);
                    $msg = 'Демо-контент оновлено на сторінках home, about, contacts';
                }
                if ($action === 'install_demo_data') {
                    $created = jura_seed_demo_posts($pdo, (int) $_SESSION['admin_user_id']);
                    $msg = $created > 0 ? "Додано демо-публікацій: {$created}" : 'Демо-публікації вже встановлені.';
                }
                if ($action === 'clear_cache') {
                    // Already flushed by the blanket admin-POST hook above;
                    // this just gives a clear confirmation message.
                    $msg = 'Кеш сторінок очищено.';
                }
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

    if ($path === '/admin/updates') {
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
                $_SESSION['update_check'] = \Core\Updater\Updater::checkForUpdates();
                redirect('/admin/updates');
            }
            if ($action === 'update_now') {
                $updateResult = \Core\Updater\Updater::runAutomaticUpdate();
                if ($updateResult['ok']) {
                    session_flash('upd_success', $updateResult['message']);
                    unset($_SESSION['update_check']);
                } else {
                    session_flash('upd_error', $updateResult['message']);
                }
                redirect('/admin/updates');
            }
        }

        $errorLogFile = BASE_PATH . '/logs/php-error.log';
        $errorLogTail = '';
        if (is_file($errorLogFile)) {
            $lines = file($errorLogFile, FILE_IGNORE_NEW_LINES) ?: [];
            $errorLogTail = implode("\n", array_slice($lines, -100));
        }
        view_admin('updates', ['title' => 'Оновлення', 'current_version' => $currentVersion, 'installed_at' => $lockData['installed_at'] ?? '', 'git_remote' => $gitRemote, 'git_branch' => $gitBranch, 'git_last_commit' => $gitLastCommit, 'error_log_tail' => $errorLogTail, 'update_check' => $_SESSION['update_check'] ?? null, 'flash_success' => session_flash('upd_success'), 'flash_error' => session_flash('upd_error')]);
        exit;
    }

    if ($path === '/admin/posts/reorder' && $method === 'POST') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (is_array($ids)) {
            foreach ($ids as $i => $id) {
                $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET sort_order=? WHERE id=?')->execute([$i + 1, (int)$id]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($path === '/admin/posts') {
        $sortMap = ['date' => 'p.published_at', 'title' => 'p.title', 'id' => 'p.id', 'order' => 'p.sort_order'];
        $sort = array_key_exists($_GET['sort'] ?? '', $sortMap) ? $_GET['sort'] : 'date';
        $dir = ($sort === 'order') ? 'ASC' : ((($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC');
        $perPageOpts = [20, 25, 30, 50, 100, 200];
        $perPage = in_array((int)($_GET['per_page'] ?? 20), $perPageOpts) ? (int)($_GET['per_page'] ?? 20) : 20;
        $total = (int)$pdo->query('SELECT COUNT(*) FROM ' . jura_table('posts'))->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $curPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
        $offset = ($curPage - 1) * $perPage;
        $orderCol = $sortMap[$sort];
        $query = "SELECT p.*,c.title category_title FROM " . jura_table('posts') . " p LEFT JOIN " . jura_table('post_category_relations') . " r ON r.post_id=p.id LEFT JOIN " . jura_table('post_categories') . " c ON c.id=r.category_id ORDER BY {$orderCol} {$dir}, p.id DESC LIMIT {$perPage} OFFSET {$offset}";
        view_admin('posts', ['title' => 'Публікації', 'posts' => $pdo->query($query)->fetchAll(), 'sort' => $sort, 'dir' => $dir, 'per_page' => $perPage, 'per_page_opts' => $perPageOpts, 'cur_page' => $curPage, 'total_pages' => $totalPages, 'total' => $total]);
        exit;
    }

    if ($path === '/admin/posts/create') {
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $status = (string) ($_POST['status'] ?? 'draft');
            $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : ($status === 'published' ? date('Y-m-d H:i:s') : null);
            [, $defaultLocale] = active_locales($pdo);
            $pdo->prepare('INSERT INTO ' . jura_table('posts') . ' (slug,title,excerpt,content,status,meta_title,meta_description,published_at,locale) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$slug, $_POST['title'], $_POST['excerpt'] ?? '', $_POST['content'] ?? '', $status, $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', $publishedAt, $defaultLocale]);
            $newPostId = (int) $pdo->lastInsertId();
            save_route($pdo, '/blog/' . $slug, 'post', $newPostId);
            redirect(isset($_POST['_close']) ? '/admin/posts' : '/admin/posts/' . $newPostId . '/edit');
        }
        view_admin('post-edit', ['title' => 'Нова публікація', 'post' => []]);
        exit;
    }

    if (preg_match('#^/admin/posts/(\d+)/translate$#', $path, $matches) && $method === 'POST') {
        $sourceId = (int) $matches[1];
        $locale = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['locale'] ?? '')));
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . ' WHERE id=?');
        $stmt->execute([$sourceId]);
        $source = $stmt->fetch();
        if ($source && $locale !== '') {
            $rootId = translation_root_id($source);
            $existing = $pdo->prepare('SELECT id FROM ' . jura_table('posts') . ' WHERE (id=? OR translation_of=?) AND locale=?');
            $existing->execute([$rootId, $rootId, $locale]);
            $newId = (int) $existing->fetchColumn();
            if (!$newId) {
                $ins = $pdo->prepare('INSERT INTO ' . jura_table('posts') . ' (slug,title,excerpt,content,status,meta_title,meta_description,locale,translation_of) VALUES (?,?,?,?,?,?,?,?,?)');
                $ins->execute([$source['slug'], $source['title'], $source['excerpt'], '', 'draft', '', '', $locale, $rootId]);
                $newId = (int) $pdo->lastInsertId();
                [, $defaultLocale] = active_locales($pdo);
                $routePrefix = $locale !== $defaultLocale ? '/' . $locale : '';
                save_route($pdo, $routePrefix . '/blog/' . $source['slug'], 'post', $newId);
            }
            redirect('/admin/posts/' . $newId . '/edit');
        }
        redirect('/admin/posts/' . $sourceId . '/edit');
    }

    if (preg_match('#^/admin/posts/(\d+)/edit$#', $path, $matches)) {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . ' WHERE id=?');
        $stmt->execute([(int) $matches[1]]);
        $post = $stmt->fetch();
        if ($method === 'POST') {
            $id = (int) $matches[1];
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $status = (string) ($_POST['status'] ?? 'draft');
            $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : ($status === 'published' ? date('Y-m-d H:i:s') : null);
            $featuredImage = $post['featured_image'] ?? null;
            $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET slug=?,title=?,excerpt=?,content=?,status=?,meta_title=?,meta_description=?,published_at=?,featured_image=?,updated_at=NOW() WHERE id=?')
                ->execute([$slug, $_POST['title'], $_POST['excerpt'] ?? '', $_POST['content'] ?? '', $status, $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', $publishedAt, $featuredImage, $id]);
            [, $defaultLocale] = active_locales($pdo);
            $postLocale = (string) ($post['locale'] ?? $defaultLocale);
            $routePrefix = $postLocale !== '' && $postLocale !== $defaultLocale ? '/' . $postLocale : '';
            $pdo->prepare('DELETE FROM ' . jura_table('routes') . " WHERE entity_type='post' AND entity_id=?")->execute([$id]);
            save_route($pdo, $routePrefix . '/blog/' . $slug, 'post', $id);
            redirect(isset($_POST['_close']) ? '/admin/posts' : '/admin/posts/' . $id . '/edit');
        }
        if ($post) {
            $post['content'] = str_replace(['src="/userfiles/', "src='/userfiles/"], ['src="/public/userfiles/', "src='/public/userfiles/"], $post['content'] ?? '');
        }
        view_admin('post-edit', [
            'title' => 'Редагувати публікацію',
            'post' => $post,
            'translations' => $post ? entity_translations($pdo, 'posts', $post) : [],
            'all_locales' => $post ? $pdo->query('SELECT code,name,native_name FROM ' . jura_table('locales') . ' ORDER BY sort_order,id')->fetchAll() : [],
        ]);
        exit;
    }

    if (preg_match('#^/admin/posts/(\d+)/upload-image$#', $path, $matches) && $method === 'POST') {
        $id = (int) $matches[1];
        $uploadDir = BASE_PATH . '/public/userfiles/posts/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $file = $_FILES['featured_image'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                $filename = 'post-' . $id . '-' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // delete old featured image if it was an uploaded one
                    $old = $pdo->prepare('SELECT featured_image FROM ' . jura_table('posts') . ' WHERE id=?');
                    $old->execute([$id]);
                    $oldImg = $old->fetchColumn();
                    if ($oldImg && str_starts_with($oldImg, 'post-') && file_exists($uploadDir . $oldImg)) {
                        @unlink($uploadDir . $oldImg);
                    }
                    $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET featured_image=? WHERE id=?')->execute([$filename, $id]);
                }
            }
        } elseif (!empty($_POST['remove_image'])) {
            $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET featured_image=NULL WHERE id=?')->execute([$id]);
        }
        redirect('/admin/posts/' . $id . '/edit');
    }

    if (preg_match('#^/admin/posts/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        $pdo->prepare('DELETE FROM ' . jura_table('posts') . ' WHERE id=?')->execute([(int) $matches[1]]);
        redirect('/admin/posts');
    }

    if (preg_match('#^/admin/posts/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('posts') . ' WHERE id=?');
        $row->execute([(int) $matches[1]]);
        $cur = (string)($row->fetchColumn() ?: 'draft');
        $next = $cur === 'published' ? 'draft' : 'published';
        $pdo->prepare('UPDATE ' . jura_table('posts') . ' SET status=?,updated_at=NOW() WHERE id=?')->execute([$next, (int)$matches[1]]);
        redirect('/admin/posts');
    }

    if (preg_match('#^/admin/pages/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('pages') . ' WHERE id=?');
        $row->execute([(int) $matches[1]]);
        $cur = (string)($row->fetchColumn() ?: 'draft');
        $next = $cur === 'published' ? 'draft' : 'published';
        $pdo->prepare('UPDATE ' . jura_table('pages') . ' SET status=?,updated_at=NOW() WHERE id=?')->execute([$next, (int)$matches[1]]);
        redirect('/admin/pages');
    }

    // --- Users ---
    if ($path === '/admin/users') {
        $users = $pdo->query('SELECT id,email,status,role,created_at FROM ' . jura_table('users') . ' ORDER BY id')->fetchAll();
        view_admin('users', ['title' => 'Користувачі', 'users' => $users, 'flash_success' => session_flash('success'), 'flash_error' => session_flash('error')]);
        exit;
    }

    if ($path === '/admin/users/create' && $method === 'POST') {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass = trim((string)($_POST['password'] ?? ''));
        $role = in_array($_POST['role'] ?? '', ['admin','editor']) ? $_POST['role'] : 'editor';
        if (!$email || !$pass) { session_flash('error', 'Email та пароль обов\'язкові.'); redirect('/admin/users'); }
        try {
            $pdo->prepare('INSERT INTO ' . jura_table('users') . ' (email,password_hash,status,role) VALUES (?,?,?,?)')
                ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), 'active', $role]);
            session_flash('success', 'Користувача створено.');
        } catch (\Throwable $e) { session_flash('error', 'Email вже існує.'); }
        redirect('/admin/users');
    }

    if (preg_match('#^/admin/users/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        $uid = (int)$matches[1];
        if ($uid !== (int)$_SESSION['admin_user_id']) {
            $pdo->prepare('DELETE FROM ' . jura_table('users') . ' WHERE id=?')->execute([$uid]);
            session_flash('success', 'Користувача видалено.');
        } else { session_flash('error', 'Не можна видалити власний акаунт.'); }
        redirect('/admin/users');
    }

    if (preg_match('#^/admin/users/(\d+)/password$#', $path, $matches) && $method === 'POST') {
        $pass = trim((string)($_POST['password'] ?? ''));
        if (strlen($pass) < 6) { session_flash('error', 'Пароль мінімум 6 символів.'); redirect('/admin/users'); }
        $pdo->prepare('UPDATE ' . jura_table('users') . ' SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$matches[1]]);
        session_flash('success', 'Пароль змінено.');
        redirect('/admin/users');
    }

    if (preg_match('#^/admin/users/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('users') . ' WHERE id=?');
        $row->execute([(int)$matches[1]]);
        $cur = (string)($row->fetchColumn() ?: 'active');
        $next = $cur === 'active' ? 'inactive' : 'active';
        $pdo->prepare('UPDATE ' . jura_table('users') . ' SET status=? WHERE id=?')->execute([$next, (int)$matches[1]]);
        redirect('/admin/users');
    }

    http_response_code(404);
    echo 'Not Found';
    exit;
}

$pdo = admin_db();
ensure_cms_schema($pdo);
ModuleLoader::ensureTable($pdo);
ModuleLoader::loadInstalled($pdo);
ModuleLoader::hookEach('ensure_schema', $pdo);

if ($method === 'POST' && preg_match('#^/forms/([a-zA-Z0-9_-]+)$#', $path, $matches)) {
    $payload = $_POST;
    unset($payload['_token']);
    $pdo->prepare('INSERT INTO ' . jura_table('form_submissions') . ' (form_code,locale,source_url,name,email,phone,message,payload_json,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$matches[1], $_POST['locale'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, $_POST['name'] ?? null, $_POST['email'] ?? null, $_POST['phone'] ?? null, $_POST['message'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null, substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    notify_lead($pdo, $matches[1], $payload);
    $thankyouUrl = trim((string)(cms_settings($pdo)['thankyou_page'] ?? ''));
    redirect($thankyouUrl !== '' ? $thankyouUrl : (string)($_SERVER['HTTP_REFERER'] ?? '/'));
}

$redirect = find_redirect($pdo, $path);
if ($redirect) {
    http_response_code((int) $redirect['status_code']);
    header('Location: ' . $redirect['target_path']);
    exit;
}

if (ModuleLoader::hookFirst('handle_frontend', $path, $pdo)) {
    exit;
}

$route = frontend_route($pdo, $path);
if ($route) {
    if ($route['entity_type'] === 'page') {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('pages') . ' WHERE id=? AND status="published" LIMIT 1');
        $stmt->execute([(int) $route['entity_id']]);
        $page = $stmt->fetch();
        if ($page) {
            $settings = cms_settings($pdo);
            $page['content'] = ModuleLoader::hookFilter('filter_page_content', $page['content'] ?? '', $settings);
            $locale = (string) ($route['locale'] ?? $page['locale'] ?? $settings['default_locale'] ?? 'uk');
            $translations = entity_translations($pdo, 'pages', $page);
            $common = ['locale' => $locale, 'translations' => $translations];
            if (($page['template'] ?? '') === 'blog') {
                $perPage = max(1, (int)($settings['blog_per_page'] ?? 12));
                // Posts inserted directly (old migrations, manual SQL) can
                // predate the locale column and sit at '' — treat those as
                // belonging to every locale rather than vanishing from the
                // blog until someone explicitly assigns them one.
                $totalPostsStmt = $pdo->prepare('SELECT COUNT(*) FROM ' . jura_table('posts') . " WHERE status='published' AND (locale=? OR locale='')");
                $totalPostsStmt->execute([$locale]);
                $totalPosts = (int) $totalPostsStmt->fetchColumn();
                $totalPages = max(1, (int)ceil($totalPosts / $perPage));
                $currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
                $offset = ($currentPage - 1) * $perPage;
                $posts = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . " WHERE status='published' AND (locale=? OR locale='') ORDER BY published_at DESC, id DESC LIMIT {$perPage} OFFSET {$offset}");
                $posts->execute([$locale]);
                frontend_render_cached($frontendCacheKey, fn() => view_frontend('blog', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'posts' => $posts->fetchAll(), 'settings' => $settings, 'pagination' => ['current' => $currentPage, 'total' => $totalPages, 'per_page' => $perPage]], $common)));
            } elseif (($page['template'] ?? '') === 'contacts') {
                frontend_render_cached($frontendCacheKey, fn() => view_frontend('contacts', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common)));
            } elseif (($page['template'] ?? '') === 'home') {
                $homeExtra = ModuleLoader::hookCollect('home_data', $pdo);
                frontend_render_cached($frontendCacheKey, fn() => view_frontend('home', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common, $homeExtra)));
            } else {
                frontend_render_cached($frontendCacheKey, fn() => view_frontend('page', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common)));
            }
            exit;
        }
    }
    if ($route['entity_type'] === 'post') {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('posts') . ' WHERE id=? AND status="published" LIMIT 1');
        $stmt->execute([(int) $route['entity_id']]);
        $post = $stmt->fetch();
        if ($post) {
            $locale = (string) ($route['locale'] ?? $post['locale'] ?? 'uk');
            frontend_render_cached($frontendCacheKey, fn() => view_frontend('post', ['title' => $post['meta_title'] ?: $post['title'], 'meta_description' => $post['meta_description'], 'post' => $post, 'settings' => cms_settings($pdo), 'locale' => $locale, 'translations' => entity_translations($pdo, 'posts', $post)]));
            exit;
        }
    }
}

http_response_code(404);
frontend_render_cached($frontendCacheKey, fn() => view_frontend('404', ['title' => '404', 'settings' => cms_settings($pdo)]));
