<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\SettingModel;
use PDO;

/** Sends a form submission to the site owner by email and/or Telegram,
 * per the notification_email_to/telegram_* settings. */
final class LeadNotifier
{
    public static function notify(PDO $pdo, string $formCode, array $payload): void
    {
        $settings = SettingModel::all($pdo);
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
            if (!is_scalar($value)) {
                continue;
            }
            $label = $fieldLabels[$key] ?? null;
            if ($label === null) {
                continue;
            }
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
}
