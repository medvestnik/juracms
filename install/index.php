<?php
declare(strict_types=1);
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', __DIR__);
}
$autoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
$helpers = BASE_PATH . '/core/Support/helpers.php';
if (is_file($helpers)) {
    require_once $helpers;
}
require_once BASE_PATH.'/core/start.php';
$configFile=BASE_PATH.'/config.php'; $lockFile=BASE_PATH.'/storage/installed.lock'; $version=trim((string)@file_get_contents(BASE_PATH.'/VERSION'))?:'0.1.0';
$config = is_file($configFile) ? require $configFile : [];
$installed = \Core\Installer\Runtime::isInstalled();
if ($installed) {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method === 'POST' && (string)($_POST['install_action'] ?? '') === 'reset-installation') {
        $token = (string)($_POST['_token'] ?? '');
        if (hash_equals(csrf_token(), $token)) {
            // isInstalled() now treats config.php's app.installed flag as
            // authoritative (a missing lock file alone is self-healed, so a
            // redeploy that wipes storage/ doesn't force reinstallation).
            // A real reset must flip that flag, not just remove the lock.
            @unlink($lockFile);
            $config['app']['installed'] = false;
            @file_put_contents($configFile, "<?php\n\nreturn " . var_export($config, true) . ";\n");
            $_SESSION['installer'] = [];
            redirect('/install/');
        }
    }
    $siteName = (string)($config['app']['name'] ?? 'Jura CMS');
    $adminEmail = null;
    $usersWarning = null;
    try {
        $pdo = db_connect((array)($config['database'] ?? []));
        $usersTable = jura_table('users');
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote(str_replace('`', '', $usersTable)));
        if ($stmt && $stmt->fetchColumn()) {
            $adminEmail = $pdo->query('SELECT email FROM ' . $usersTable . ' ORDER BY id ASC LIMIT 1')->fetchColumn() ?: null;
        } else {
            $usersWarning = 'Таблица пользователей не найдена';
        }
    } catch (Throwable $e) {
        $usersWarning = 'База данных недоступна: ' . $e->getMessage();
    }
    $justInstalled = (string) ($_GET['done'] ?? '') === '1';
    include __DIR__ . '/views/installed.php';
    exit;
}
$installerDefaults = [
    'step' => 1,
    'db' => ['host' => '127.0.0.1', 'port' => '3306', 'database' => '', 'username' => '', 'password' => '', 'prefix' => 'jura_'],
    'admin' => ['site_name' => 'Jura CMS', 'admin_name' => '', 'admin_email' => ''],
];
$installerState = $_SESSION['installer'] ?? [];
if (!is_array($installerState)) { $installerState = []; }
$installerState = array_replace_recursive($installerDefaults, $installerState);
if (!isset($installerState['step']) || !is_numeric($installerState['step'])) { $installerState['step'] = 1; }
$installerState['step'] = max(1, min(4, (int) $installerState['step']));
$_SESSION['installer'] = $installerState;
$state=&$_SESSION['installer']; $step=(int)$state['step']; $errors=[]; $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
if ($method==='POST') { $action=(string)($_POST['install_action']??'');
// PHP sessions are unreliable on some hosts (see core/start.php); the wizard
// also round-trips its state through hidden "carry_*" fields in the browser
// so a broken session doesn't silently drop earlier steps' input.
foreach (['host','port','database','username','password','prefix'] as $f) {
    if (isset($_POST['carry_db_'.$f]) && $_POST['carry_db_'.$f] !== '') { $state['db'][$f] = (string) $_POST['carry_db_'.$f]; }
}
foreach (['site_name','admin_name','admin_email'] as $f) {
    if (isset($_POST['carry_admin_'.$f]) && $_POST['carry_admin_'.$f] !== '') { $state['admin'][$f] = (string) $_POST['carry_admin_'.$f]; }
}
if (!empty($_POST['carry_admin_password_hash'])) { $state['admin_password_hash'] = (string) $_POST['carry_admin_password_hash']; }
if($action==='environment-next'){$step=2;$state['step']=2;}
if($action==='db-next'){foreach(['host','port','database','username','password','prefix'] as $f){$state['db'][$f]=trim((string)($_POST['db_'.$f]??$state['db'][$f]));} try{db_connect($state['db']);$step=3;$state['step']=3;}catch(Throwable $e){$errors[]=$e->getMessage();$step=2;}}
if($action==='admin-next'){foreach(['site_name','admin_name','admin_email'] as $f){$state['admin'][$f]=trim((string)($_POST[$f]??$state['admin'][$f]));}$p=(string)($_POST['admin_password']??'');$c=(string)($_POST['admin_password_confirmation']??''); if(!filter_var($state['admin']['admin_email'],FILTER_VALIDATE_EMAIL)||$p!==$c||strlen($p)<8){$errors[]='Проверьте email и пароль (минимум 8 символов).';$step=3;} else{$state['admin_password_hash']=password_hash($p,PASSWORD_DEFAULT);$step=4;$state['step']=4;}}
if($action==='install-run'){ try { @set_time_limit(180); @ignore_user_abort(true); $db=$state['db']; $a=$state['admin']; $pdo=db_connect($db); $p=preg_replace('/[^a-zA-Z0-9_]/','',(string)$db['prefix'])?:'jura_';
$tables=[
"{$p}user_groups"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(120) UNIQUE,name VARCHAR(191),description TEXT NULL,is_system TINYINT(1) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}users"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,group_id INT UNSIGNED,name VARCHAR(191),email VARCHAR(191) UNIQUE,password_hash VARCHAR(255),status VARCHAR(40) DEFAULT 'active',email_verified_at TIMESTAMP NULL,last_login_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}permissions"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(120) UNIQUE,name VARCHAR(191),description TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}group_permissions"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,group_id INT UNSIGNED,permission_id INT UNSIGNED,UNIQUE KEY uq_gp (group_id,permission_id)",
"{$p}settings"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,setting_key VARCHAR(191) UNIQUE,setting_value TEXT NULL,setting_type VARCHAR(40) DEFAULT 'string',group_name VARCHAR(80) DEFAULT 'system',updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}pages"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,parent_id INT UNSIGNED NULL,author_id INT UNSIGNED,title VARCHAR(191),slug VARCHAR(191),content MEDIUMTEXT NULL,excerpt TEXT NULL,status VARCHAR(20),template VARCHAR(80) NULL,meta_title VARCHAR(191) NULL,meta_description TEXT NULL,meta_keywords TEXT NULL,sort_order INT DEFAULT 0,published_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}posts"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,author_id INT UNSIGNED,title VARCHAR(191),slug VARCHAR(191),content MEDIUMTEXT NULL,excerpt TEXT NULL,featured_image_id INT UNSIGNED NULL,status VARCHAR(20),meta_title VARCHAR(191) NULL,meta_description TEXT NULL,published_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}post_categories"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,parent_id INT UNSIGNED NULL,title VARCHAR(191),slug VARCHAR(191),description TEXT NULL,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}post_category_relations"=>"post_id INT UNSIGNED,category_id INT UNSIGNED,PRIMARY KEY(post_id,category_id)","{$p}tags"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(191),slug VARCHAR(191),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}tag_relations"=>"tag_id INT UNSIGNED,entity_type VARCHAR(40),entity_id INT UNSIGNED,PRIMARY KEY(tag_id,entity_type,entity_id)","{$p}menus"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(120) UNIQUE,name VARCHAR(191),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}menu_items"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,menu_id INT UNSIGNED,parent_id INT UNSIGNED NULL,title VARCHAR(191),url VARCHAR(191),entity_type VARCHAR(40) NULL,entity_id INT UNSIGNED NULL,target VARCHAR(20) DEFAULT '_self',sort_order INT DEFAULT 0,status VARCHAR(20) DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}blocks"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(120) UNIQUE,title VARCHAR(191),type VARCHAR(60),content MEDIUMTEXT NULL,settings_json JSON NULL,status VARCHAR(20) DEFAULT 'active',sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}media_files"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED,disk VARCHAR(40),path VARCHAR(255),filename VARCHAR(191),original_name VARCHAR(191),mime_type VARCHAR(120),size BIGINT,width INT NULL,height INT NULL,alt VARCHAR(191) NULL,title VARCHAR(191) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}product_categories"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,parent_id INT UNSIGNED NULL,title VARCHAR(191),slug VARCHAR(191),description TEXT NULL,sort_order INT DEFAULT 0,status VARCHAR(20) DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}products"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,category_id INT UNSIGNED NULL,title VARCHAR(191),slug VARCHAR(191),sku VARCHAR(80),description MEDIUMTEXT NULL,short_description TEXT NULL,price DECIMAL(12,2) DEFAULT 0,old_price DECIMAL(12,2) DEFAULT 0,currency VARCHAR(10) DEFAULT 'USD',quantity INT DEFAULT 0,status VARCHAR(20) DEFAULT 'draft',featured_image_id INT UNSIGNED NULL,meta_title VARCHAR(191) NULL,meta_description TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}product_images"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED,media_file_id INT UNSIGNED,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP","{$p}product_attributes"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(191),code VARCHAR(120),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
"{$p}product_attribute_values"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED,attribute_id INT UNSIGNED,value TEXT", "{$p}routes"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,path VARCHAR(191) UNIQUE,entity_type VARCHAR(40),entity_id INT UNSIGNED,status VARCHAR(20) DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", "{$p}redirects"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,source_path VARCHAR(191),target_path VARCHAR(191),status_code SMALLINT DEFAULT 301,is_active TINYINT(1) DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", "{$p}migrations"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,migration VARCHAR(191),batch INT DEFAULT 1,executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP", "{$p}activity_logs"=>"id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NULL,action VARCHAR(120),entity_type VARCHAR(40),entity_id INT UNSIGNED NULL,message TEXT NULL,ip_address VARCHAR(45) NULL,user_agent VARCHAR(255) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];
if (!empty($_POST['clean_install'])) {
    $dropNames = array_map(fn($n) => "`{$n}`", array_merge(array_keys($tables), ["{$p}locales", "{$p}form_submissions", "{$p}sessions"]));
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS ' . implode(',', $dropNames));
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}
foreach($tables as $n=>$d){$pdo->exec("CREATE TABLE IF NOT EXISTS `{$n}` ({$d}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
$groups=['users','authors','editors','moderators','administrators','super_administrators']; foreach($groups as $g){$pdo->prepare('INSERT IGNORE INTO '.jura_table('user_groups').' (code,name,description,is_system) VALUES (?,?,?,1)')->execute([$g,ucwords(str_replace('_',' ',$g)),$g]);}
$perms=['dashboard.view','users.view','users.create','users.edit','users.delete','groups.view','groups.edit','pages.view','pages.create','pages.edit','pages.delete','posts.view','posts.create','posts.edit','posts.delete','products.view','products.create','products.edit','products.delete','media.view','media.upload','settings.view','settings.edit','system.update'];
foreach($perms as $c){$pdo->prepare('INSERT IGNORE INTO '.jura_table('permissions').' (code,name,description) VALUES (?,?,?)')->execute([$c,$c,$c]);}
$super=(int)$pdo->query('SELECT id FROM '.jura_table('user_groups')." WHERE code='super_administrators' LIMIT 1")->fetch()['id']; $adminGroup=(int)$pdo->query('SELECT id FROM '.jura_table('user_groups')." WHERE code='administrators' LIMIT 1")->fetch()['id'];
$pdo->prepare('INSERT INTO '.jura_table('users').' (group_id,name,email,password_hash,status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),group_id=VALUES(group_id),name=VALUES(name),password_hash=VALUES(password_hash),status=VALUES(status)')->execute([$super,$a['admin_name'],$a['admin_email'],$state['admin_password_hash'],'active']);
$uid=(int)$pdo->lastInsertId();
$allPermIds=$pdo->query('SELECT id FROM '.jura_table('permissions'))->fetchAll(PDO::FETCH_COLUMN); foreach($allPermIds as $pid){$pdo->prepare('INSERT IGNORE INTO '.jura_table('group_permissions').' (group_id,permission_id) VALUES (?,?)')->execute([$super,$pid]); if((int)$pid!== (int)$pdo->query('SELECT id FROM '.jura_table('permissions')." WHERE code='system.update'")->fetch()['id']){$pdo->prepare('INSERT IGNORE INTO '.jura_table('group_permissions').' (group_id,permission_id) VALUES (?,?)')->execute([$adminGroup,$pid]);}}
$homeContent='<p>Jura CMS &mdash; це легка система керування сайтом із класичною установкою в корінь хостингу та сучасною адмін-панеллю. Створюйте сторінки, ведіть блог, керуйте медіатекою та меню &mdash; все з коробки.</p><p>Ця сторінка, як і сторінки &laquo;Про нас&raquo; та &laquo;Контакти&raquo;, &mdash; демонстраційний контент. Відредагуйте або видаліть його в розділі <strong>Сторінки</strong> адмін-панелі.</p>';
$pdo->prepare('INSERT INTO '.jura_table('pages').' (parent_id,author_id,title,slug,content,excerpt,status,template,published_at) VALUES (NULL,?,?,?,?,?,?,?,NOW())')->execute([$uid,'Главная','home',$homeContent,'Jura CMS &mdash; легка CMS з класичною установкою та сучасною адмін-панеллю.','published','home']);
$aboutContent='<p>'.e($a['site_name']).' працює на Jura CMS &mdash; системі керування сайтом, що поєднує простоту класичних движків із зручністю адмін-панелі нового покоління.</p><p>У цьому розділі зазвичай розповідають історію компанії, місію та команду. Замініть цей текст власним описом у розділі <strong>Сторінки</strong>.</p><div class="feature-grid" style="margin-top:1.75rem"><div class="feature-card"><div class="feature-card__icon">🎯</div><h3>Місія</h3><p>Дати простий та зрозумілий інструмент для створення й розвитку сайту.</p></div><div class="feature-card"><div class="feature-card__icon">⚡</div><h3>Швидкість</h3><p>Мінімум залежностей, встановлення в корінь хостингу за кілька хвилин.</p></div><div class="feature-card"><div class="feature-card__icon">🤝</div><h3>Підтримка</h3><p>Оновлення та документація для впевненого старту й розвитку проєкту.</p></div></div>';
$pdo->prepare('INSERT INTO '.jura_table('pages').' (parent_id,author_id,title,slug,content,status,published_at) VALUES (NULL,?,?,?,?,?,NOW())')->execute([$uid,'О нас','about',$aboutContent,'published']);
$aboutId=(int)$pdo->lastInsertId();
$contactsContent='<p>Залишились питання? Напишіть нам &mdash; форма нижче надсилає повідомлення прямо на пошту адміністратора сайту.</p>';
$pdo->prepare('INSERT INTO '.jura_table('pages').' (parent_id,author_id,title,slug,content,status,template,published_at) VALUES (NULL,?,?,?,?,?,?,NOW())')->execute([$uid,'Контакты','contacts',$contactsContent,'published','contacts']); $contactsId=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT IGNORE INTO '.jura_table('post_categories').' (title,slug,description) VALUES (?,?,?)')->execute(['Новости','news','']);
$pdo->prepare('INSERT IGNORE INTO '.jura_table('menus').' (code,name) VALUES (?,?)')->execute(['main','Main']); $menuId=(int)$pdo->query('SELECT id FROM '.jura_table('menus')." WHERE code='main'")->fetch()['id']; foreach([['Главная','/'],['О нас','/about']] as $i=>$mi){$pdo->prepare('INSERT INTO '.jura_table('menu_items').' (menu_id,title,url,sort_order,status) VALUES (?,?,?,?,?)')->execute([$menuId,$mi[0],$mi[1],$i+1,'active']);}

$blogPageTitle='Блог';
$pdo->prepare('INSERT INTO '.jura_table('pages').' (parent_id,author_id,title,slug,content,excerpt,status,template,published_at) VALUES (NULL,?,?,?,?,?,?,?,NOW())')->execute([$uid,$blogPageTitle,'blog','Новости, заметки и обновления Jura CMS.','Новости, заметки и обновления Jura CMS.','published','blog']);
$blogPageId=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT IGNORE INTO '.jura_table('menu_items').' (menu_id,title,url,sort_order,status) VALUES (?,?,?,?,?)')->execute([$menuId,'Блог','/blog',3,'active']);
$pdo->prepare('INSERT IGNORE INTO '.jura_table('menu_items').' (menu_id,title,url,sort_order,status) VALUES (?,?,?,?,?)')->execute([$menuId,'Контакты','/contacts',4,'active']);
$catId=(int)$pdo->query('SELECT id FROM '.jura_table('post_categories')." WHERE slug='news' LIMIT 1")->fetch()['id'];
$demoPosts=[['Welcome to Jura CMS','welcome-to-jura-cms','Jura CMS is ready for your first website.','Краткое описание возможностей Jura CMS.'],['Why Jura CMS is simple to install','simple-installation','Classic installation flow with a modern admin panel.','Описание идеи установки в корень сайта, config.php и install wizard.'],['Jura UI inside the admin panel','jura-ui-admin-panel','A clean UI foundation for the next generation CMS.','Описание Jura UI.'],['Built-in Simple JS Editor','built-in-simple-js-editor','A lightweight editor for pages, posts and content blocks.','Описание встроенного редактора.'],['Pages, posts and media in one place','pages-posts-media','Manage your content with a clear structure.','Описание страниц, записей, медиа.'],['Roadmap for Jura CMS','roadmap','The next steps: templates, plugins, products and updates.','Описание планов CMS.']];
foreach($demoPosts as $dp){$pdo->prepare('INSERT INTO '.jura_table('posts').' (author_id,title,slug,excerpt,content,status,published_at) VALUES (?,?,?,?,?,?,NOW())')->execute([$uid,$dp[0],$dp[1],$dp[2],$dp[3],'published']);$postId=(int)$pdo->lastInsertId();$pdo->prepare('INSERT IGNORE INTO '.jura_table('post_category_relations').' (post_id,category_id) VALUES (?,?)')->execute([$postId,$catId]);$pdo->prepare('INSERT IGNORE INTO '.jura_table('routes').' (path,entity_type,entity_id,status) VALUES (?,?,?,?)')->execute(['/blog/'.$dp[1],'post',$postId,'active']);}
foreach(['/public/assets/frontend/img/juracms-logo.png'=>'Jura CMS Logo','/public/assets/frontend/img/juracms-banner.png'=>'Jura CMS Banner'] as $asset=>$title){$abs=BASE_PATH.$asset;if(is_file($abs)){$size=(int)filesize($abs);$mime=function_exists('mime_content_type')?mime_content_type($abs):'image/png';$name=basename($abs);$pdo->prepare('INSERT INTO '.jura_table('media_files').' (user_id,disk,path,filename,original_name,mime_type,size,title) VALUES (?,?,?,?,?,?,?,?)')->execute([$uid,'public',str_replace(BASE_PATH.'/','',$abs),$name,$name,(string)$mime,$size,$title]);}}
foreach([['/','page',1],['/about','page',$aboutId],['/contacts','page',$contactsId],['/blog','page',$blogPageId]] as $r){$pdo->prepare('INSERT IGNORE INTO '.jura_table('routes').' (path,entity_type,entity_id,status) VALUES (?,?,?,?)')->execute([$r[0],$r[1],$r[2],'active']);}
$settings=[['site_name',$a['site_name']],['cms_version',$version],['default_locale','ru'],['frontend_theme','default'],['admin_theme','jura'],['editor','simple'],['homepage_id','1'],['contact_email',$a['admin_email']],['notification_email_to',$a['admin_email']]];
$settingsColumns=[]; foreach($pdo->query('SHOW COLUMNS FROM '.jura_table('settings')) as $col){$settingsColumns[]=(string)$col['Field'];}
$hasSettingType=in_array('setting_type',$settingsColumns,true); $hasGroupName=in_array('group_name',$settingsColumns,true);
foreach($settings as $s){
if($hasSettingType&&$hasGroupName){$pdo->prepare('INSERT INTO '.jura_table('settings').' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$s[0],$s[1],'string','system']);}
elseif($hasSettingType){$pdo->prepare('INSERT INTO '.jura_table('settings').' (setting_key,setting_value,setting_type) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$s[0],$s[1],'string']);}
elseif($hasGroupName){$pdo->prepare('INSERT INTO '.jura_table('settings').' (setting_key,setting_value,group_name) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$s[0],$s[1],'system']);}
else{$pdo->prepare('INSERT INTO '.jura_table('settings').' (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$s[0],$s[1]]);}
}
file_put_contents($configFile,"<?php\n\nreturn ".var_export(['app'=>['name'=>$a['site_name'],'installed'=>true,'version'=>$version],'database'=>['connection'=>'mysql','host'=>$db['host'],'port'=>(int)$db['port'],'database'=>$db['database'],'username'=>$db['username'],'password'=>$db['password'],'prefix'=>$p,'charset'=>'utf8mb4']],true).";\n");
file_put_contents($lockFile,json_encode(['installed_at'=>date(DATE_ATOM),'site_name'=>$a['site_name']],JSON_PRETTY_PRINT)); $_SESSION['installer']=[]; redirect('/install/?done=1');
} catch (Throwable $e) { $errors[]='Помилка встановлення: '.$e->getMessage(); $step=4; $state['step']=4; }
}
}
$checks=['PHP >= 8.1'=>version_compare(PHP_VERSION,'8.1.0','>='),'pdo_mysql'=>extension_loaded('pdo_mysql'),'mbstring'=>extension_loaded('mbstring'),'json'=>extension_loaded('json')];
include __DIR__.'/views/wizard.php';
