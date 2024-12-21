<?php

namespace App\Service\AmoCRM;

use DateTime;
use Exception;
use League\OAuth2\Client\Token\AccessToken;

class AmoLeadService
{

    private AccessToken $accessToken;
    private string $baseUrl;

    private AmoRequestService $amoRequestService;
    private WebHookService $webhookService;

    function __construct($accessToken)
    {

        $this->amoRequestService = new AmoRequestService();
        $this->accessToken = $accessToken;
        $this->baseUrl = "https://{$accessToken->getValues()['baseDomain']}";
        $this->webhookService = new WebhookService();
    }


    /**
     * @throws Exception
     */
    public function doHookData($leadId, $hookDataDTO): array
    {

        $leadResponse = $this->getLeadById($leadId);
        if ($leadResponse['status'] >= 400) {
            return $leadResponse;
        }
        //Вывод полей сделки
        //return $leadResponse;


        $leadArray = $leadResponse['data']['custom_fields_values'];

        $this->writeLeadToHookData($leadArray, $hookDataDTO);

        // return $leadArray;

        $contactId = $leadResponse['data']['_embedded']['contacts'][0]['id']; // Получаем все ID контактов

        $contactResponse = $this->getContactsByIds($contactId); // Получаем подробности о контактах

        if ($contactResponse['status'] >= 400) {
            return $contactResponse;

        }

        $contactName = $contactResponse['data']['name'];
        $hookDataDTO->setContactName($contactName);

        $contactArray = $contactResponse['data']['custom_fields_values'];

        $this->writeContactToHookData($contactArray, $hookDataDTO);


        // return $leadArray;
        return ['status' => 200, 'data' => "Успешное заполнение хука"];
    }

    /**
     * @throws Exception
     */
    private function writeLeadToHookData($data, $hookDataDTO): void
    {
        try {


            $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];

            $createdReserve = $this->getCreatedReserve($data, $amoFieldsConfig['createReserveField']);
            $dateReserve = $this->getDateReserve($data, $amoFieldsConfig['dataReserveField']);
            $timeReserve = $this->getTimeReserve($data, $amoFieldsConfig['timeReserveField']);
            $countPeople = $this->getCountPeople($data, $amoFieldsConfig['countPeopleField']);
            $nameReserve = $this->getNameReserve($data, $amoFieldsConfig['nameReserveField']);
            $IdReserve = $this->getIdReserve($data, $amoFieldsConfig['idReserveField']);

            if (!empty($dateReserve) && !empty($timeReserve)) {
                $date = DateTime::createFromFormat('U.u', $dateReserve . '.0')->setTime(0, 0, 0);
                // Форматируем в нужный вид
                $formattedDate = $date->format('Y-m-d H:i:s.v');

                preg_match('/с (\d{2}:\d{2}) по (\d{2}:\d{2})/', $timeReserve, $matches);
                $startTime = $matches[1]; // 14:00
                $endTime = $matches[2];   // 16:00
                $datetime = new DateTime($formattedDate);
                $datetime->setTime((int)substr($startTime, 0, 2), (int)substr($startTime, 3, 2));
                $startTimeMinutes = (int)substr($startTime, 0, 2) * 60 + (int)substr($startTime, 3, 2);
                $endTimeMinutes = (int)substr($endTime, 0, 2) * 60 + (int)substr($endTime, 3, 2);
                $timeDifference = $endTimeMinutes - $startTimeMinutes;


                $hookDataDTO->setDataReserve($datetime->format('Y-m-d H:i:s.v'));
                $hookDataDTO->setTimeReserve($timeDifference);
            }

            $hookDataDTO->setCreatedReserve($createdReserve);
            $hookDataDTO->setCountPeople($countPeople);
            $hookDataDTO->setNameReserve($nameReserve);
            $hookDataDTO->setIdReserve($IdReserve);

        } catch (Exception $e) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод ошибки: " . print_r($hookDataDTO, true));

        }

    }


    private function writeContactToHookData($data, $hookDataDTO): void
    {
        try {

            foreach ($data as $item) {
                //   $hookData[$item['id']] = $item['values'];

                if ($item['field_code'] == 'EMAIL') {
                    $hookDataDTO->setContactEmail($item['values'][0]['value']);

                }
                if ($item['field_code'] == 'PHONE') {
                    $hookDataDTO->setContactPhone($item['values'][0]['value']);

                }
            }
        } catch (Exception $e) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод ошибки: " . print_r($hookDataDTO, true));

        }
    }

    private function getLeadById($leadId): array
    {
        $url = "{$this->baseUrl}/api/v4/leads/{$leadId}?with=contacts";

        $response = $this->amoRequestService->makeRequest('GET', $url, $this->accessToken);

        return $response;
    }

    private function getContactsByIds($contactId): array
    {
        // $ids = implode(',', $contactIds); // Формируем строку с ID через запятую
        $url = "{$this->baseUrl}/api/v4/contacts/{$contactId}";

        $response = $this->amoRequestService->makeRequest('GET', $url, $this->accessToken);


        return $response;
    }


    private function getCreatedReserve($data, mixed $createReserveField): bool
    {
        $createdReserve = false;
        foreach ($data as $item) {
            if ($item['field_id'] == $createReserveField['id']) {
                if ($item['values'][0]['enum_id'] == $createReserveField['Yes'])
                    $createdReserve = true;
                break;

            }
        }
        return $createdReserve;
    }


    private function getValueReserve($data, $inputField): bool
    {

        $fieldValue = "";
        foreach ($data as $item) {
            if ($item['field_id'] == $inputField) {
                $fieldValue = $item['values'][0]['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }

        }
        return $fieldValue;

    }


    private function getDateReserve($data, mixed $dataReserveField)
    {
        $dateReserve = "";
        foreach ($data as $item) {
            if ($item['field_id'] == $dataReserveField) {
                $dateReserve = $item['values'][0]['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }

        }
        return $dateReserve;

    }

    private function getCountPeople($data, mixed $countPeopleField)
    {
        $countPeople = "";
        foreach ($data as $item) {
            if ($item['field_id'] == $countPeopleField) {
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
            if ($item['field_id'] == $timeReserveField) {
                $timeReserve = $item['values'][0]['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }

        }
        return $timeReserve;

    }

    private function getNameReserve($data, mixed $nameReserveField)
    {
        $nameReserve = "";
        foreach ($data as $item) {
            if ($item['field_id'] == $nameReserveField) {
                $nameReserve = $item['values'][0]['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }
        }
        return $nameReserve;

    }


    private function getIdReserve($data, mixed $idReserveField)
    {
        $idReserve = "";
        foreach ($data as $item) {
            if ($item['field_id'] == $idReserveField) {
                $idReserve = $item['values'][0]['value'];  // Извлекаем значение
                break;  // Прерываем цикл, так как мы нашли нужный элемент
            }
        }
        return $idReserve;

    }

}