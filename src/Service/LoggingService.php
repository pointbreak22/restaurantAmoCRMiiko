<?php

declare(strict_types=1);

namespace App\Service;

class LoggingService
{
    public static function save(mixed $arData, string $type = '', string $directory = ''): bool
    {
        $path = (!empty($directory)) ? APP_PATH . '/var/log/' . $directory . '/' : APP_PATH . '/var/log/';
        file_exists($path) || mkdir($path, 0777, true);

        $dataToJson = [
            date('H:i:s') => [
                'type' => $type,
                'data' => $arData,
            ],
        ];

        $file = date('Y-m-d') . '.json';

        //    mkdir(dirname($path . $file), 0777, true); // Создаем директорию с правами 0777
        if (file_exists($path . $file)) {
            $oldJsonLog = json_decode(file_get_contents($path . $file), true);
            $jsonLog = json_encode(array_merge($dataToJson, $oldJsonLog), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $jsonLog = json_encode($dataToJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return (bool)file_put_contents($path . $file, $jsonLog);
    }
}