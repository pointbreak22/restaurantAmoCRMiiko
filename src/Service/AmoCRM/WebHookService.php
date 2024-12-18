<?php

namespace App\Service\AmoCRM;

define('AMO_WEBHOOK_FILE', APP_PATH . '/var/tmp/webhook.log');
define('AMO_WEBHOOK_FILE_LEADS', APP_PATH . '/var/tmp/webhook_leads.log');


class WebHookService
{
    public function startProcessing()
    {
        // Логируем данные для отладки
        $input = file_get_contents('php://input');
        $resultData = [];
        $data = $_POST;

        //   $this->logToFile(AMO_WEBHOOK_FILE, 'Webhook received: 11111111' . print_r($data, true));
        //  $this->logToFile(AMO_WEBHOOK_FILE, $data);

        // Проверка на наличие данных о лидах
        if (isset($data["leads"]["update"][0]['custom_fields'])) {

            try {
                $resultData = $this->getHookValues($data["leads"]["update"][0]['custom_fields']);
                $resultData['leadId'] = $data["leads"]["update"][0]['id'];
                //     $this->logToFile(AMO_WEBHOOK_FILE, 'Webhook received: 22222222' . print_r($resultData, true));

            } catch (\Exception $e) {
                $this->logToFile(AMO_WEBHOOK_FILE, $e->getMessage());
            }


        } else {
            $this->logToFile(AMO_WEBHOOK_FILE, 'No leads data found in the request.c5555555');
        }

        $this->logToFile(AMO_WEBHOOK_FILE, 'End Webhook received');
        // Возвращаем ответ
        header('Content-Type: application/json');

        echo json_encode(['status' => 'success', "data" => $data]);
        http_response_code(200);
        return $resultData;

        //dd($data);

    }

    public function logToFile(string $filename, string $message): void
    {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        // Создаем файл, если его нет, и записываем данные
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0777, true); // Создаем директорию с правами 0777
        }

        file_put_contents($filename, $logMessage, FILE_APPEND | LOCK_EX);


    }

    private function getHookValues($data): array
    {
        $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];
        $hookData = [];

        // return $data[0];


        //  return $amoFieldsConfig;
        foreach ($data as $item) {
            //   $hookData[$item['id']] = $item['values'];

            if ($item['id'] == $amoFieldsConfig['createReserveField']) {
                if ($item['values']['value'] != "Да")
                    $hookData = [];
                break;

            }
            if ($item['id'] == $amoFieldsConfig['dataReserveField']) {
                $hookData['dataReserveField'] = $item['values'][0];  // Извлекаем значение
                //     continue;  // Прерываем цикл, так как мы нашли нужный элемент
            }
            if ($item['id'] == $amoFieldsConfig['countPeopleField']) {
                $hookData['countPeopleField'] = $item['values'][0]['value'];  // Извлекаем значение
                //   continue;  // Прерываем цикл, так как мы нашли нужный элемент
            }
            if ($item['id'] == $amoFieldsConfig['timeField']) {

                $hookData['timeField'] = $this->getRangeMinutes($item['values']['value']);  // Извлекаем значение
                //    continue;  // Прерываем цикл, так как мы нашли нужный элемент
            }
            if ($item['id'] == $amoFieldsConfig['nameReserveField']) {

                $hookData['nameReserveField'] = $item['values']['value'];  // Извлекаем значение
                //continue;  // Прерываем цикл, так как мы нашли нужный элемент
            }
        }


        return $hookData;


    }

    private function getRangeMinutes($timeRange): float|int
    {
        // Извлекаем время начала и конца из строки
        preg_match('/(\d{2}:\d{2})/', $timeRange, $startTimeMatch);
        preg_match('/по (\d{2}:\d{2})/', $timeRange, $endTimeMatch);

        $startTime = $startTimeMatch[1]; // Начальное время
        $endTime = $endTimeMatch[1];     // Конечное время

// Преобразуем строки времени в объекты DateTime
        $start = \DateTime::createFromFormat('H:i', $startTime);
        $end = \DateTime::createFromFormat('H:i', $endTime);

// Получаем разницу в минутах
        $interval = $start->diff($end);
        $minutes = $interval->h * 60 + $interval->i;

        return $minutes;

    }
}