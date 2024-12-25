<?php

namespace App\Service\AmoCRM;


use App\Service\LoggingService;
use Exception;


define('AMO_WEBHOOK_FILE', APP_PATH . '/var/webhook/webhook.log');
define('AMO_WEBHOOK_FILE_LEADS', APP_PATH . '/var/webhook/webhook_leads.log');


class WebHookService
{


    public function getLeadId()
    {
        $leadId = "";
        $data = $_POST;

        //  return $data;
        // Проверка на наличие данных о лидах
        if (isset($data["leads"]["update"][0]['custom_fields'])) {
            try {
                //  $this->setHookValues($data["leads"]["update"][0]['custom_fields'], $this->hookDataDTO);
                $leadId = $data["leads"]["update"][0]['id'];
            } catch (Exception $e) {

                LoggingService::save($e->getMessage(), LOG_ERR, "Errors");
            }

        } elseif (isset($data["leads"]["add"][0]['custom_fields'])) {
            try {
                //       $this->setHookValues($data["leads"]["add"][0]['custom_fields'], $this->hookDataDTO);
                $leadId = $data["leads"]["add"][0]['id'];

            } catch (Exception $e) {

                LoggingService::save($e->getMessage(), LOG_ERR, "Errors");
            }


        } else {

            LoggingService::save("No leads data found in the request.", LOG_ERR, "Errors");
        }
        return $leadId;
    }


//    public function logToFile(string $filename, string $message): void
//    {
//        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
//        // Создаем файл, если его нет, и записываем данные
//        if (!file_exists(dirname($filename))) {
//            mkdir(dirname($filename), 0777, true); // Создаем директорию с правами 0777
//        }
//        file_put_contents($filename, $logMessage, FILE_APPEND | LOCK_EX);
//
//    }

}


