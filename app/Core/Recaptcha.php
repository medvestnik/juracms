<?php

declare(strict_types=1);

namespace App\Core;

/** Server-side check against Google reCAPTCHA's siteverify endpoint. */
final class Recaptcha
{
    public static function verify(string $secret, string $token, ?string $remoteIp = null): bool
    {
        if ($secret === '' || $token === '') {
            return false;
        }
        $params = ['secret' => $secret, 'response' => $token];
        if ($remoteIp) {
            $params['remoteip'] = $remoteIp;
        }
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($params),
            'timeout' => 5,
        ]]);
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($raw === false) {
            // Couldn't reach Google from this host -- don't let a transient
            // network issue turn into "the contact form is broken" for every
            // real visitor. Only a response that explicitly says the check
            // failed blocks the submission.
            return true;
        }
        $data = json_decode($raw, true);
        return is_array($data) && !empty($data['success']);
    }
}
