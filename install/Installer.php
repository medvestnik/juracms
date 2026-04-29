<?php

declare(strict_types=1);

final class Installer
{
    public static function steps(): array
    {
        return [
            'environment' => 'Проверка окружения',
            'database' => 'Подключение к базе данных',
            'admin' => 'Создание администратора',
            'finish' => 'Завершение установки',
        ];
    }
}
