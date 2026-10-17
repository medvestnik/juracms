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
        $payload = $_POST;
        unset($payload['_token'], $payload['g-recaptcha-response']);
        FormSubmissionModel::insert($pdo, $formCode, $payload);
        LeadNotifier::notify($pdo, $formCode, $payload);
        $thankyouUrl = trim((string) ($settings['thankyou_page'] ?? ''));
        redirect($thankyouUrl !== '' ? $thankyouUrl : (string) ($_SERVER['HTTP_REFERER'] ?? '/'));
    }
}
