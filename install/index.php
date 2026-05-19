<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', __DIR__);
}

$autoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$coreStart = BASE_PATH . '/core/start.php';
if (file_exists($coreStart)) {
    require_once $coreStart;
}

$configFile = BASE_PATH . '/config.php';
$lockFile = BASE_PATH . '/storage/installed.lock';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$version = trim((string) @file_get_contents(BASE_PATH . '/VERSION')) ?: '0.1.0-alpha';

if (!isset($_SESSION['installer'])) {
    $_SESSION['installer'] = [];
}

$errors = [];
$success = null;
$step = max(1, (int) ($_SESSION['installer']['step'] ?? 1));
$db = (array) ($_SESSION['installer']['db'] ?? ['host'=>'127.0.0.1','port'=>'3306','database'=>'','username'=>'','password'=>'','prefix'=>'jura_']);
$admin = (array) ($_SESSION['installer']['admin'] ?? ['site_name'=>'','admin_name'=>'','admin_email'=>'']);

$installedConfig = is_file($configFile) ? ((array) require $configFile) : [];
$fullyInstalled = is_file($configFile) && is_file($lockFile) && (($installedConfig['app']['installed'] ?? false) === true);

if ($fullyInstalled && $method === 'POST' && (($_POST['install_action'] ?? '') === 'reset_installation')) {
    $tokenInput = (string) ($_POST['install_reset_token'] ?? '');
    $confirm = ($_POST['confirm_reset'] ?? '') === '1';
    $expectedToken = (string) ($installedConfig['security']['install_reset_token'] ?? '');

    if (!$confirm) {
        $errors[] = 'Подтвердите сброс установки.';
    } elseif ($expectedToken === '' || !hash_equals($expectedToken, $tokenInput)) {
        $errors[] = 'Неверный reset token.';
    } else {
        if (is_file($lockFile)) {
            @unlink($lockFile);
        }
        $_SESSION['installer'] = [];
        redirect('/install/');
    }
}

if (!$fullyInstalled && $method === 'POST') {
    $action = (string) ($_POST['install_action'] ?? '');
    $requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
    $checks = ['PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='), 'vendor/autoload.php' => is_file($autoload)];
    foreach ($requiredExt as $ext) { $checks['extension: '.$ext] = extension_loaded($ext); }
    foreach (['root'=>BASE_PATH,'storage'=>BASE_PATH.'/storage','uploads'=>BASE_PATH.'/uploads','cache'=>BASE_PATH.'/cache','logs'=>BASE_PATH.'/logs'] as $k=>$dir) {
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $checks['writable: '.$k] = is_writable($dir);
    }
    $environmentOk = !in_array(false, $checks, true);

    if ($action === 'environment-next') {
        if (!$environmentOk) { $errors[] = 'Сервер не прошёл проверки окружения.'; $step = 1; }
        else { $_SESSION['installer']['step']=2; $step=2; }
    }

    if ($action === 'db-test') {
        $db = ['host'=>trim((string)($_POST['db_host']??'')),'port'=>trim((string)($_POST['db_port']??'3306')),'database'=>trim((string)($_POST['db_database']??'')),'username'=>trim((string)($_POST['db_username']??'')),'password'=>(string)($_POST['db_password']??''),'prefix'=>trim((string)($_POST['db_prefix']??'jura_')),'connection'=>'mysql','charset'=>'utf8mb4'];
        if ($db['host']===''||$db['database']===''||$db['username']==='') $errors[]='Заполните host, database, username.';
        if (!$errors) {
            try { db_connect($db); $_SESSION['installer']['db']=$db; $_SESSION['installer']['step']=3; $step=3; }
            catch (Throwable $e) { $errors[]='Ошибка подключения к базе данных: '.$e->getMessage(); $step=2; }
        } else { $step=2; }
    }

    if ($action === 'admin-next') {
        $admin = ['site_name'=>trim((string)($_POST['site_name']??'')),'admin_name'=>trim((string)($_POST['admin_name']??'')),'admin_email'=>trim((string)($_POST['admin_email']??''))];
        $password=(string)($_POST['admin_password']??''); $confirm=(string)($_POST['admin_password_confirmation']??'');
        if ($admin['site_name']==='') $errors[]='Поле site_name обязательно.';
        if ($admin['admin_name']==='') $errors[]='Поле admin_name обязательно.';
        if (!filter_var($admin['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[]='Укажите корректный admin_email.';
        if (mb_strlen($password)<8) $errors[]='Пароль минимум 8 символов.';
        if ($password!==$confirm) $errors[]='Пароли не совпадают.';
        if (!$errors) { $_SESSION['installer']['admin']=$admin; $_SESSION['installer']['admin_password_hash']=password_hash($password,PASSWORD_DEFAULT); $_SESSION['installer']['step']=4; $step=4; }
        else { $step=3; }
    }

    if ($action === 'install-run') {
        $db=(array)($_SESSION['installer']['db']??[]); $admin=(array)($_SESSION['installer']['admin']??[]); $hash=(string)($_SESSION['installer']['admin_password_hash']??'');
        if (!$db || !$admin || $hash==='') { $errors[]='Сессия установки неполная.'; $step=2; }
        else {
            try {
                $pdo=db_connect($db); $prefix=preg_replace('/[^a-zA-Z0-9_]/','',(string)($db['prefix']??'jura_'))?:'jura_';
                $resetToken=bin2hex(random_bytes(32));
                $config=['app'=>['name'=>$admin['site_name'],'url'=>((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https://':'http://').($_SERVER['HTTP_HOST']??'localhost'),'env'=>'production','debug'=>false,'key'=>bin2hex(random_bytes(16)),'installed'=>true,'version'=>$version],'database'=>['connection'=>'mysql','host'=>$db['host'],'port'=>(int)$db['port'],'database'=>$db['database'],'username'=>$db['username'],'password'=>$db['password'],'prefix'=>$prefix,'charset'=>'utf8mb4'],'security'=>['install_reset_token'=>$resetToken,'full_reset_allowed'=>false]];
                $export = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
                if (file_put_contents($configFile, $export) === false) throw new RuntimeException('Не удалось создать config.php');

                $adminsTable=sprintf('`%sadmins`',$prefix); $settingsTable=sprintf('`%ssettings`',$prefix);
                $pdo->exec("CREATE TABLE IF NOT EXISTS {$adminsTable} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(191) NOT NULL,email VARCHAR(191) NOT NULL UNIQUE,password_hash VARCHAR(255) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->exec("CREATE TABLE IF NOT EXISTS {$settingsTable} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,setting_key VARCHAR(191) NOT NULL UNIQUE,setting_value TEXT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $ins=$pdo->prepare("INSERT INTO {$adminsTable} (name,email,password_hash) VALUES (:name,:email,:password_hash)");
                $ins->execute(['name'=>$admin['admin_name'],'email'=>$admin['admin_email'],'password_hash'=>$hash]);
                $set=$pdo->prepare("INSERT INTO {$settingsTable} (setting_key, setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
                $set->execute(['k'=>'site_name','v'=>$admin['site_name']]);
                $set->execute(['k'=>'cms_version','v'=>$version]);

                $lock=['installed_at'=>(new DateTimeImmutable())->format(DATE_ATOM),'version'=>$version,'mode'=>'web-installer','site_name'=>$admin['site_name'],'admin_email'=>$admin['admin_email']];
                if (file_put_contents($lockFile, json_encode($lock, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL)===false) throw new RuntimeException('Не удалось создать installed.lock');
                $_SESSION['installer']=[];
                redirect('/admin/login');
            } catch (Throwable $e) { $errors[]='Установка не завершена: '.$e->getMessage(); if (is_file($lockFile)) @unlink($lockFile); $step=4; }
        }
    }
}

$lockData = is_file($lockFile) ? (json_decode((string) file_get_contents($lockFile), true) ?: []) : [];
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Jura CMS Installer</title><link rel="stylesheet" href="/public/assets/cms/installer.css"></head><body><div class="installer-shell"><h1>Установка Jura CMS</h1>
<?php foreach ($errors as $error): ?><p style="color:#b00020;"><?= e($error) ?></p><?php endforeach; ?>
<?php if ($fullyInstalled): ?>
<p>Jura CMS уже установлена</p>
<ul><li>site_name: <?= e((string)($lockData['site_name'] ?? $installedConfig['app']['name'] ?? '')) ?></li><li>app.url: <?= e((string)($installedConfig['app']['url'] ?? '')) ?></li><li>version: <?= e((string)($lockData['version'] ?? $installedConfig['app']['version'] ?? '')) ?></li><li>installed_at: <?= e((string)($lockData['installed_at'] ?? '')) ?></li><li>admin_email: <?= e((string)($lockData['admin_email'] ?? '')) ?></li></ul>
<p><a href="/">Открыть сайт</a> | <a href="/admin/login">Войти в админку</a></p>
<form method="post"><input type="hidden" name="install_action" value="reset_installation"><input name="install_reset_token" placeholder="install_reset_token"><label><input type="checkbox" name="confirm_reset" value="1"> Я понимаю, что установка будет сброшена</label><button type="submit">Reset installation</button></form>
<?php else: ?>
<p>Шаг <?= $step ?> из 4</p>
<form method="post">
<?php if ($step===1): ?><input type="hidden" name="install_action" value="environment-next"><button>Продолжить</button><?php endif; ?>
<?php if ($step===2): ?><input type="hidden" name="install_action" value="db-test"><input name="db_host" value="<?=e($db['host'])?>" placeholder="DB host"><input name="db_port" value="<?=e($db['port'])?>" placeholder="DB port"><input name="db_database" value="<?=e($db['database'])?>" placeholder="DB name"><input name="db_username" value="<?=e($db['username'])?>" placeholder="DB username"><input name="db_password" type="password" placeholder="DB password"><input name="db_prefix" value="<?=e($db['prefix'])?>" placeholder="DB prefix"><button>Проверить БД</button><?php endif; ?>
<?php if ($step===3): ?><input type="hidden" name="install_action" value="admin-next"><input name="site_name" value="<?=e($admin['site_name'])?>" placeholder="site_name"><input name="admin_name" value="<?=e($admin['admin_name'])?>" placeholder="admin_name"><input name="admin_email" value="<?=e($admin['admin_email'])?>" placeholder="admin_email"><input type="password" name="admin_password" placeholder="admin_password"><input type="password" name="admin_password_confirmation" placeholder="admin_password_confirmation"><button>Продолжить</button><?php endif; ?>
<?php if ($step===4): ?><input type="hidden" name="install_action" value="install-run"><button>Выполнить установку</button><?php endif; ?>
</form>
<?php endif; ?>
</div></body></html>
