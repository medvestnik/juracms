<?php

declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\LeadNotifier;
use App\Models\FormSubmissionModel;
use App\Models\SettingModel;
use PDO;

final class FormController
{
    public static function submit(PDO $pdo, string $formCode): never
    {
        $payload = $_POST;
        unset($payload['_token']);
        FormSubmissionModel::insert($pdo, $formCode, $payload);
        LeadNotifier::notify($pdo, $formCode, $payload);
        $thankyouUrl = trim((string) (SettingModel::all($pdo)['thankyou_page'] ?? ''));
        redirect($thankyouUrl !== '' ? $thankyouUrl : (string) ($_SERVER['HTTP_REFERER'] ?? '/'));
    }
}
