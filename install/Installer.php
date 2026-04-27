<?php

declare(strict_types=1);

final class Installer
{
    public static function steps(): array
    {
        return [
            'environment' => 'Проверка окружения',
            'permissions' => 'Права на директории',
            'database' => 'Подключение к базе данных',
            'env' => 'Генерация .env',
            'admin' => 'Создание администратора',
            'migrations' => 'Выполнение миграций',
            'finish' => 'Завершение установки',
        ];
    }

    public static function complete(): void
    {
        $storage = BASE_PATH . '/storage';
        if (!is_dir($storage)) {
            mkdir($storage, 0775, true);
        }

        file_put_contents($storage . '/installed.lock', (new DateTimeImmutable())->format(DATE_ATOM));
        $_SESSION['installed_version'] = trim((string) @file_get_contents(BASE_PATH . '/VERSION')) ?: '0.0.0';
    }
}
