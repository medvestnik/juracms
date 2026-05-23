<?php

declare(strict_types=1);
$autoload = __DIR__ . '/vendor/autoload.php'; if (is_file($autoload)) require_once $autoload;
require_once __DIR__ . '/core/start.php';
use Core\Installer\Runtime as InstallerRuntime;
function admin_db(): PDO { return db_connect((array) cms_config('database', [])); }
function frontend_route(string $path): ?array { try{$pdo=admin_db();$t=jura_table('routes');$s=$pdo->prepare("SELECT * FROM {$t} WHERE path=:p AND status='active' LIMIT 1");$s->execute(['p'=>$path]);return $s->fetch()?:null;}catch(Throwable){return null;} }
function ensure_path(string $p): string { $p='/' . trim($p,'/'); return $p==='//' ? '/' : $p; }
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/'; $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
if (!InstallerRuntime::isInstalled() && !in_array($path,['/install','/install/'],true)) redirect('/install/');
if ($method!=='GET' && !str_starts_with($path,'/admin')) { http_response_code(405); echo 'Method Not Allowed'; exit; }
if ($path==='/admin/page') redirect('/admin/pages');
if ($path==='/admin/login') { /* omitted keep old */ }
switch (true) {
case $path==='/admin/login':
    if (!InstallerRuntime::isInstalled()) redirect('/install/');
    $dbConfig=(array) cms_config('database', []);
    if ($method==='POST') { $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??'');
        try{$pdo=db_connect($dbConfig);$t=jura_table('users');$st=$pdo->prepare("SELECT id,email,password_hash,status FROM {$t} WHERE email=:email LIMIT 1");$st->execute(['email'=>$email]);$u=$st->fetch(); if(!$u||$u['status']!=='active'||!password_verify($password,(string)$u['password_hash'])) throw new RuntimeException('bad'); $_SESSION['admin_user_id']=(int)$u['id'];$_SESSION['admin_user_email']=$u['email'];redirect('/admin');}catch(Throwable){session_flash('auth_error','Неверный email/пароль.');redirect('/admin/login');}}
    if (admin_is_authenticated()) redirect('/admin'); view_admin('login',['title'=>'Sign in','layout'=>'auth','error'=>session_flash('auth_error')]); exit;
case $path==='/admin/logout': if($method!=='POST'){http_response_code(405);exit;} unset($_SESSION['admin_user_id'],$_SESSION['admin_user_email']); redirect('/admin/login');
default:
}
if (str_starts_with($path,'/admin')) { admin_require_auth(); $pdo=admin_db();
    if ($path==='/admin'||$path==='/admin/users'||$path==='/admin/user-groups'||$path==='/admin/settings') {
        $viewMap=['/admin'=>'dashboard','/admin/users'=>'users','/admin/user-groups'=>'user-groups','/admin/settings'=>'settings']; $view=$viewMap[$path]??'dashboard';
        $data=['title'=>ucwords(str_replace(['-','/admin'],' ',trim($path,'/')))?:'Dashboard'];
        $data['stats']=['pages'=>(int)$pdo->query('SELECT COUNT(*) FROM '.jura_table('pages'))->fetchColumn(),'posts'=>(int)$pdo->query('SELECT COUNT(*) FROM '.jura_table('posts'))->fetchColumn(),'media'=>(int)$pdo->query('SELECT COUNT(*) FROM '.jura_table('media_files'))->fetchColumn(),'users'=>(int)$pdo->query('SELECT COUNT(*) FROM '.jura_table('users'))->fetchColumn()];
        view_admin($view,$data); exit;
    }
    if ($path==='/admin/pages'&&$method==='GET'){ $data=['title'=>'Pages']; $data['pages']=$pdo->query('SELECT p.*,r.path route_path FROM '.jura_table('pages').' p LEFT JOIN '.jura_table('routes')." r ON r.entity_type='page' AND r.entity_id=p.id ORDER BY p.id")->fetchAll(); view_admin('pages',$data); exit; }
    if ($path==='/admin/pages/create'&&$method==='GET'){ view_admin('pages',['title'=>'Add page','edit'=>null]); exit; }
    if ($path==='/admin/pages'&&$method==='POST'){ $slug=trim($_POST['slug']?:slugify((string)$_POST['title'])); $route=ensure_path((string)($_POST['route_path']??('/'.$slug))); if($route==='/home')$route='/'; $st=$pdo->prepare('INSERT INTO '.jura_table('pages').' (author_id,title,slug,content,excerpt,status,template,meta_title,meta_description,sort_order,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,CASE WHEN ?="published" THEN NOW() ELSE NULL END)'); $status=$_POST['status']??'draft'; $st->execute([(int)$_SESSION['admin_user_id'],$_POST['title'],$slug,$_POST['content']??'',$_POST['excerpt']??'',$status,$_POST['template']??'page',$_POST['meta_title']??'',$_POST['meta_description']??'',(int)($_POST['sort_order']??0),$status]); $id=(int)$pdo->lastInsertId(); $pdo->prepare('INSERT INTO '.jura_table('routes').' (path,entity_type,entity_id,status) VALUES (?,?,?,"active") ON DUPLICATE KEY UPDATE entity_type=VALUES(entity_type),entity_id=VALUES(entity_id),status="active"')->execute([$route,'page',$id]); redirect('/admin/pages'); }
    if (preg_match('#^/admin/pages/(\d+)/edit$#',$path,$m)&&$method==='GET'){ $id=(int)$m[1]; $st=$pdo->prepare('SELECT p.*,r.path route_path FROM '.jura_table('pages')." p LEFT JOIN ".jura_table('routes')." r ON r.entity_type='page' AND r.entity_id=p.id WHERE p.id=?"); $st->execute([$id]); view_admin('pages',['title'=>'Edit page','edit'=>$st->fetch()]); exit; }
    if (preg_match('#^/admin/pages/(\d+)$#',$path,$m)&&$method==='POST'){ $id=(int)$m[1]; $slug=trim($_POST['slug']?:slugify((string)$_POST['title'])); $route=ensure_path((string)($_POST['route_path']??('/'.$slug))); $pdo->prepare('UPDATE '.jura_table('pages').' SET title=?,slug=?,content=?,excerpt=?,status=?,template=?,meta_title=?,meta_description=?,sort_order=?,updated_at=NOW() WHERE id=?')->execute([$_POST['title'],$slug,$_POST['content']??'',$_POST['excerpt']??'',$_POST['status']??'draft',$_POST['template']??'page',$_POST['meta_title']??'',$_POST['meta_description']??'',(int)($_POST['sort_order']??0),$id]); $pdo->prepare('DELETE FROM '.jura_table('routes')." WHERE entity_type='page' AND entity_id=?")->execute([$id]); $pdo->prepare('INSERT INTO '.jura_table('routes').' (path,entity_type,entity_id,status) VALUES (?,?,?,"active")')->execute([$route,'page',$id]); redirect('/admin/pages'); }
    if (preg_match('#^/admin/pages/(\d+)/delete$#',$path,$m)&&$method==='POST'){ $id=(int)$m[1]; $pdo->prepare('DELETE FROM '.jura_table('routes')." WHERE entity_type='page' AND entity_id=?")->execute([$id]); $pdo->prepare('DELETE FROM '.jura_table('pages').' WHERE id=?')->execute([$id]); redirect('/admin/pages'); }
    if ($path==='/admin/posts'&&$method==='GET'){ $q='SELECT p.*,c.title category_title FROM '.jura_table('posts').' p LEFT JOIN '.jura_table('post_category_relations').' r ON r.post_id=p.id LEFT JOIN '.jura_table('post_categories').' c ON c.id=r.category_id ORDER BY p.id DESC'; view_admin('posts',['title'=>'Posts','posts'=>$pdo->query($q)->fetchAll(),'categories'=>$pdo->query('SELECT * FROM '.jura_table('post_categories'))->fetchAll()]); exit; }
    http_response_code(404); echo 'Not Found'; exit;
}
$route=frontend_route($path);
if ($route) { $pdo=admin_db();
    if ($route['entity_type']==='page'){ $st=$pdo->prepare('SELECT * FROM '.jura_table('pages').' WHERE id=? AND status="published" LIMIT 1');$st->execute([(int)$route['entity_id']]); $page=$st->fetch(); if($page){ if(($page['template']??'')==='blog'){ $posts=$pdo->query('SELECT * FROM '.jura_table('posts')." WHERE status='published' ORDER BY published_at DESC, id DESC")->fetchAll(); view_frontend('blog',['title'=>$page['title'],'page'=>$page,'posts'=>$posts]); } else { view_frontend(($page['template']??'')==='home'?'home':'page',['title'=>$page['title'],'page'=>$page]); } exit; }}
    if ($route['entity_type']==='post'){ $st=$pdo->prepare('SELECT * FROM '.jura_table('posts').' WHERE id=? AND status="published" LIMIT 1');$st->execute([(int)$route['entity_id']]); if($post=$st->fetch()){ view_frontend('post',['title'=>$post['title'],'post'=>$post]); exit; }}
}
http_response_code(404); view_frontend('404',['title'=>'404']);
