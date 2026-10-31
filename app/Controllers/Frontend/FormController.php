<?php

declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\LeadNotifier;
use App\Core\Recaptcha;
use App\Models\FormSubmissionModel;
use App\Models\SettingModel;
use PDO;

final class FormController
{
    public static function submit(PDO $pdo, string $formCode): never
    {
        $settings = SettingModel::all($pdo);
        $siteKey = trim((string) ($settings['recaptcha_site_key'] ?? ''));
        $secretKey = trim((string) ($settings['recaptcha_secret_key'] ?? ''));
        // Only gate submissions once both keys are configured -- an empty
        // secret means reCAPTCHA isn't set up yet, so don't block every
        // form on this site over a token that was never going to exist.
        if ($siteKey !== '' && $secretKey !== '') {
            $token = (string) ($_POST['g-recaptcha-response'] ?? '');
            if (!Recaptcha::verify($secretKey, $token, $_SERVER['REMOTE_ADDR'] ?? null)) {
                // Not session_flash(): anonymous frontend GETs are served
                // straight from the whole-page file cache before the app
                // ever starts a session (see the cache check at the top of
                // index.php), so a flash set here would never be read back
                // on a cache hit -- it'd just sit in the session forever. A
                // query param on the redirect works with that cache instead
                // of fighting it (it's part of the cache key).
                $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '/');
                $separator = str_contains($referrer, '?') ? '&' : '?';
                redirect($referrer . $separator . 'form_error=' . rawurlencode($formCode));
            }
        }
        $name=trim((string)($_POST['name']??''));
        $phone=trim((string)($_POST['phone']??''));
        if($name==='' || ($phone==='' && trim((string)($_POST['email']??''))==='')){
            redirect('/contacts?form_error='.rawurlencode($formCode));
        }
        if(!empty($_POST['website_url'])) {
            redirect('/contacts?form_sent='.rawurlencode($formCode));
        }
        $payload=$_POST;
        unset($payload['_token'],$payload['g-recaptcha-response'],$payload['website_url']);
        FormSubmissionModel::insert($pdo,$formCode,$payload);
        try { LeadNotifier::notify($pdo,$formCode,$payload); }
        catch(\Throwable $e) { error_log('JuraCMS lead notification failed: '.$e->getMessage()); }
        $thankyouUrl=trim((string)($settings['thankyou_page']??''));
        if($thankyouUrl!=='' && str_starts_with($thankyouUrl,'/') && !str_starts_with($thankyouUrl,'//')) {
            redirect($thankyouUrl);
        }
        $referrer=(string)($_SERVER['HTTP_REFERER']??'/contacts');
        $refpath=(string)(parse_url($referrer,PHP_URL_PATH)?:'/contacts');
        if(!str_starts_with($refpath,'/') || str_starts_with($refpath,'//'))$refpath='/contacts';
        redirect($refpath.'?form_sent='.rawurlencode($formCode));
    }
}
