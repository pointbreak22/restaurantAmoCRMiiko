<?php

namespace App\Service\AmoCRM\Core;

use App\DTO\LeadDTO;

/**
 * Создание, чтение, удаление записей из JSON-файла
 */
class AmoQueueService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = APP_PATH . '/var/queue/amocrm.json';
        file_exists(dirname($this->filePath)) || mkdir(dirname($this->filePath), 0777, true);
    }

    /**
     * Добавить запись в JSON-файл
     *
     * @param LeadDTO $lead
     * @return void
     */
    public function create(LeadDTO $lead): void
    {
        $records = $this->readAll(); // Читаем все записи
        $records[] = serialize($lead); // Добавляем новую запись в массив
        file_put_contents($this->filePath, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); // Сохраняем в файл
    }

    /**
     * Получить и удалить первую запись из JSON-файла
     *
     * @return array|null Возвращает запись или null, если файл пуст
     */
    public function popFirst(): ?LeadDTO
    {
        $records = $this->readAll();

        if (empty($records)) {
            return null;
        }

        $firstRecord = unserialize(array_shift($records));
        file_put_contents($this->filePath, json_encode($records, JSON_PRETTY_PRINT));

        return $firstRecord;
    }

    /**
     * Прочитать все записи из JSON-файла
     *
     * @return array
     */
    private function readAll(): array
    {
        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            $decoded = json_decode($content, true) ?? []; // Декодируем содержимое файла

            // Преобразуем ассоциативные массивы в объекты LeadDTO
            return array_map(function ($record) {
                return $record;
            }, $decoded);
        } else {
            return [];
        }
    }
}
