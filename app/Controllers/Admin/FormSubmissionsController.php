<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class FormSubmissionsController
{
    public static function index(PDO $pdo): never
    {
        $table = \jura_table('form_submissions');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30;
        $total = (int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
        $pages = max(1, (int)ceil($total/$limit));
        $page = min($page,$pages);
        $offset = ($page-1)*$limit;
        $rows = $pdo->query('SELECT id,form_code,name,email,phone,message,source_url,payload_json,created_at FROM '.$table.' ORDER BY id DESC LIMIT '.$limit.' OFFSET '.$offset)->fetchAll(PDO::FETCH_ASSOC);
        \view_admin('form_submissions', ['title'=>'Заявки з сайту','submissions'=>$rows,'page'=>$page,'pages'=>$pages,'total'=>$total]);
        exit;
    }
}
