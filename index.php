<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/core/start.php';

use Core\Installer\Runtime as InstallerRuntime;

function jura_prefix(): string { $db=(array) cms_config('database', []); return preg_replace('/[^a-zA-Z0-9_]/','',(string)($db['prefix']??'jura_'))?:'jura_'; }
function jura_table(string $name): string { return sprintf('`%s%s`', str_replace('`','', jura_prefix()), $name); }
function admin_db(): PDO { return db_connect((array) cms_config('database', [])); }
function frontend_route(string $path): ?array {
    try { $pdo=admin_db(); $t=jura_table('routes'); $s=$pdo->prepare("SELECT * FROM {$t} WHERE path=:p AND status='active' LIMIT 1"); $s->execute(['p'=>$path]); return $s->fetch()?:null; } catch (Throwable) { return null; }
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!InstallerRuntime::isInstalled() && !in_array($path, ['/install', '/install/'], true)) {
    redirect('/install/');
}

if ($method !== 'GET' && !in_array($path, ['/admin/login', '/admin/logout'], true)) {
    http_response_code(405); echo 'Method Not Allowed'; exit;
}

switch (rtrim($path, '/') ?: '/') {
    case '/admin/login':
        if (!InstallerRuntime::isInstalled()) { redirect('/install/'); }
        $dbConfig=(array) cms_config('database', []);
        if ($method==='POST') {
            $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??'');
            if ($email===''||$password==='') { session_flash('auth_error','Введите email и пароль.'); redirect('/admin/login'); }
            try {
                $pdo=db_connect($dbConfig); $t=jura_table('users');
                $st=$pdo->prepare("SELECT id,email,password_hash,status FROM {$t} WHERE email=:email LIMIT 1"); $st->execute(['email'=>$email]); $u=$st->fetch();
                if (!$u || $u['status']!=='active' || !password_verify($password,(string)$u['password_hash'])) { session_flash('auth_error','Неверный email/пароль или пользователь неактивен.'); redirect('/admin/login'); }
                $_SESSION['admin_user_id']=(int)$u['id']; $_SESSION['admin_user_email']=(string)$u['email']; redirect('/admin');
            } catch (Throwable $e) { session_flash('auth_error','Ошибка авторизации: '.$e->getMessage()); redirect('/admin/login'); }
        }
        if (admin_is_authenticated()) { redirect('/admin'); }
        view_admin('login',['title'=>'Sign in','layout'=>'auth','error'=>session_flash('auth_error')]); break;
    case '/admin/logout':
        if ($method!=='POST'){http_response_code(405);echo 'Method Not Allowed';exit;} unset($_SESSION['admin_user_id'],$_SESSION['admin_user_email']); redirect('/admin/login');
    case '/admin': case '/admin/users': case '/admin/user-groups': case '/admin/pages': case '/admin/posts': case '/admin/products': case '/admin/media': case '/admin/settings':
        admin_require_auth();
        $viewMap=['/admin'=>'dashboard','/admin/users'=>'users','/admin/user-groups'=>'user-groups','/admin/pages'=>'pages','/admin/posts'=>'posts','/admin/products'=>'products','/admin/media'=>'media','/admin/settings'=>'settings'];
        $view=$viewMap[rtrim($path,'/')?:'/admin'];
        $data=['title'=>ucwords(str_replace(['-','/admin'],' ',trim($path,'/')))?:'Dashboard'];
        try {
            $pdo=admin_db();
            if ($view==='users') { $data['users']=$pdo->query('SELECT u.id,u.name,u.email,u.status,u.created_at,g.name as group_name FROM '.jura_table('users').' u LEFT JOIN '.jura_table('user_groups').' g ON g.id=u.group_id ORDER BY u.id')->fetchAll(); }
            if ($view==='user-groups') { $data['groups']=$pdo->query('SELECT * FROM '.jura_table('user_groups').' ORDER BY id')->fetchAll(); }
            if ($view==='pages') { $data['pages']=$pdo->query('SELECT id,title,slug,status,created_at FROM '.jura_table('pages').' ORDER BY id')->fetchAll(); }
        } catch (Throwable) {}
        view_admin($view,$data); break;
    case '/install': require __DIR__.'/install/index.php'; break;
    default:
        $route=frontend_route($path);
        if ($route) { view_frontend('route',['title'=>'Jura CMS','route'=>$route]); break; }
        if ($path==='/') { view_frontend('home',['title'=>'Jura CMS']); break; }
        http_response_code(404); view_frontend('404',['title'=>'404']); break;
}
