<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Writes jura_form_submissions -- the raw lead rows behind /forms/{code}. */
final class FormSubmissionModel
{
    public static function insert(PDO $pdo, string $formCode, array $payload): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('form_submissions') . ' (form_code,locale,source_url,name,email,phone,message,payload_json,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $formCode,
                $payload['locale'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null,
                $payload['name'] ?? null,
                $payload['email'] ?? null,
                $payload['phone'] ?? null,
                $payload['message'] ?? null,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
    }
}
