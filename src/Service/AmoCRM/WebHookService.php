<?php

namespace App\Service\AmoCRM;

use App\DTO\HookDataDTO;
use DateTime;
use Exception;

define('AMO_WEBHOOK_FILE', APP_PATH . '/var/tmp/webhook.log');
define('AMO_WEBHOOK_FILE_LEADS', APP_PATH . '/var/tmp/webhook_leads.log');


class WebHookService
{

    private HookDataDTO $hookDataDTO;

    function __construct()
    {
        $this->hookDataDTO = new HookDataDTO();
    }


    public function startProcessing()
    {
        // Логируем данные для отладки

        //  $data = json_decode(file_get_contents('php://input'), true);
        $data = $_POST;
        // return $data;
        // Проверка на наличие данных о лидах
        if (isset($data["leads"]["update"][0]['custom_fields'])) {
            try {
                $this->setHookValues($data["leads"]["update"][0]['custom_fields'], $this->hookDataDTO);
                $this->hookDataDTO->setLeadId($data["leads"]["update"][0]['id']);

            } catch (Exception $e) {
                $this->logToFile(AMO_WEBHOOK_FILE, $e->getMessage());
            }


        } elseif (isset($data["leads"]["add"][0]['custom_fields'])) {
            try {
                $this->setHookValues($data["leads"]["add"][0]['custom_fields'], $this->hookDataDTO);
                $this->hookDataDTO->setLeadId($data["leads"]["add"][0]['id']);

            } catch (Exception $e) {
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
        return $this->hookDataDTO;

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

    /**
     * @throws Exception
     */
    private function setHookValues($data, HookDataDTO $hookDataDTO): void
    {
        $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];
        $createdReserve = $this->getCreatedReserve($data, $amoFieldsConfig['createReserveField']);
        $dateReserve = $this->getDateReserve($data, $amoFieldsConfig['dataReserveField']);
        $date = DateTime::createFromFormat('U.u', $dateReserve . '.0')->setTime(0, 0, 0);

        // Форматируем в нужный вид
        $formattedDate = $date->format('Y-m-d H:i:s.v');
        $timeReserve = $this->getTimeReserve($data, $amoFieldsConfig['timeReserveField']);
        preg_match('/с (\d{2}:\d{2}) по (\d{2}:\d{2})/', $timeReserve, $matches);
        $startTime = $matches[1]; // 14:00
        $endTime = $matches[2];   // 16:00
        $datetime = new DateTime($formattedDate);
        $datetime->setTime((int)substr($startTime, 0, 2), (int)substr($startTime, 3, 2));
        $startTimeMinutes = (int)substr($startTime, 0, 2) * 60 + (int)substr($startTime, 3, 2);
        $endTimeMinutes = (int)substr($endTime, 0, 2) * 60 + (int)substr($endTime, 3, 2);
        $timeDifference = $endTimeMinutes - $startTimeMinutes;

        $countPeople = $this->getCountPeople($data, $amoFieldsConfig['countPeopleField']);
        $nameReserve = $this->getNameReserve($data, $amoFieldsConfig['nameReserveField']);
        $IdReserve = $this->getIdReserve($data, $amoFieldsConfig['idReserveField']);
        $hookDataDTO->setDataReserve($datetime->format('Y-m-d H:i:s.v'));
        $hookDataDTO->setTimeReserve($timeDifference);
        $hookDataDTO->setCountPeople($countPeople);
        $hookDataDTO->setNameReserve($nameReserve);
        $hookDataDTO->setCreatedReserve($createdReserve);
        $hookDataDTO->setIdReserve($IdReserve);

    }

    private function getCreatedReserve($data, mixed $createReserveField): bool
    {
        $createdReserve = false;
        foreach ($data as $item) {
            if ($item['id'] == $createReserveField) {
                if ($item['values']['value'] == "Да")
                    $createdReserve = true;
                break;

            }
        }
        return $createdReserve;
    }

    private function getDateReserve($data, mixed $dataReserveField)
    {
        $dateReserve = "";
        foreach ($data as $item) {
            if ($item['id'] == $dataReserveField) {
                $dateReserve = $item['values'][0];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }

        }
        return $dateReserve;

    }

    private function getCountPeople($data, mixed $countPeopleField)
    {
        $countPeople = "";
        foreach ($data as $item) {
            if ($item['id'] == $countPeopleField) {
                $countPeople = $item['values'][0]['value'];  // Извлекаем значение
                break; // Прерываем цикл, так как мы нашли нужный элемент
            }

        }
        return $countPeople;

    }

    private function getTimeReserve($data, mixed $timeReserveField)
    {
        $timeReserve = "";
        foreach ($data as $item) {
            if ($item['id'] == $timeReserveField) {
                $timeReserve = $item['values']['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }

        }
        return $timeReserve;

    }

    private function getNameReserve($data, mixed $nameReserveField)
    {
        $nameReserve = "";
        foreach ($data as $item) {
            if ($item['id'] == $nameReserveField) {
                $nameReserve = $item['values']['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }
        }
        return $nameReserve;

    }


    private function getIdReserve($data, mixed $idReserveField)
    {
        $idReserve = "";
        foreach ($data as $item) {
            if ($item['id'] == $idReserveField) {
                $idReserve = $item['values'][0]['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }
        }
        return $idReserve;

    }
}



//[5] => Array
//(
//    [field_id] => 591687
//                    [field_name] => Создан резерв
//[field_code] =>
//                    [field_type] => radiobutton
//[values] => Array
//(
//    [0] => Array
//    (
//        [value] => Да
//        [enum_id] => 934359
//                                    [enum_code] =>
//                                )
//
//                        )
//
//                )
