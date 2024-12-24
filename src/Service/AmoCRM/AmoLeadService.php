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
        //  return $leadResponse;
        $leadArray = $leadResponse['data']['custom_fields_values'];
        $this->writeLeadToHookData($leadArray, $hookDataDTO);
        $contactId = $leadResponse['data']['_embedded']['contacts'][0]['id']; // Получаем все ID контактов
        $contactResponse = $this->getContactsByIds($contactId); // Получаем подробности о контактах
        if ($contactResponse['status'] >= 400) {
            return $contactResponse;
        }
        $contactName = $contactResponse['data']['name'];
        $hookDataDTO->setContactName($contactName);
        $contactArray = $contactResponse['data']['custom_fields_values'];
        $this->writeContactToHookData($contactArray, $hookDataDTO);
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
                $date = DateTime::createFromFormat('U.u', $dateReserve . '.0');//->setTime(0, 0, 0);
                $pattern = '/с (\d{1,2}:\d{2}) до (\d{1,2}:\d{2})/';
                $formattedDate = $date->format('Y-m-d H:i:s.v'); //13384888885
                preg_match($pattern, $timeReserve, $matches);

                // Получаем начало и конец периода
                $startTime = $matches[1]; // Например, "07:00"
                $endTime = $matches[2];   // Например, "9:00"

                // Создаем объекты DateTime для начала и конца периода
                $startDateTime = clone $date;
                $endDateTime = clone $date;

                $startDateTime->modify($startTime);
                $endDateTime->modify($endTime);

                $durationInMinutes = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60;

                $hookDataDTO->setDataReserve($startDateTime->format('Y-m-d H:i:s.v'));
                $hookDataDTO->setTimeReserve($durationInMinutes);
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

        $url = "{$this->baseUrl}/api/v4/contacts/{$contactId}";
        $response = $this->amoRequestService->makeRequest('GET', $url, $this->accessToken);
        return $response;
    }

    public function addNoteToLead(int $leadId, string $text): mixed
    {
        //   return [$leadId, $text];
        $url = "{$this->baseUrl}/api/v4/leads/notes";

        // Формирование данных примечания
        $data = [
            [
                "entity_id" => $leadId,
                "note_type" => "common", // Тип примечания (обычное текстовое примечание)
                "params" => [
                    "text" => $text
                ],

            ]
        ];

        // Отправка POST-запроса
        return $this->amoRequestService->makeRequest('POST', $url, $this->accessToken, $data);
    }

    public function editReserveInfo(int $leadId, string $value): mixed
    {
        $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];
        $url = "{$this->baseUrl}/api/v4/leads/{$leadId}?disable_webhooks=1";
        $data = [
            'custom_fields_values' => [
                [
                    'field_id' => (int)$amoFieldsConfig['idReserveField'],
                    'values' => [
                        ['value' => $value !== null ? $value : 'Default Value'] // Установка текстового значения

                    ]
                ]
            ],
            'request_id' => uniqid(), // Уникальный идентификатор запроса

        ];
        return $this->amoRequestService->makeRequest('PATCH', $url, $this->accessToken, $data);
    }


    public function editCreatedReserveInfo(int $leadId): mixed
    {
        $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];

        $url = "{$this->baseUrl}/api/v4/leads/{$leadId}?disable_webhooks=1";
        $data = [
            'custom_fields_values' => [
                [
                    'field_id' => (int)$amoFieldsConfig['createReserveField']['id'],
                    'values' => [
                        ['enum_id' => (int)$amoFieldsConfig['createReserveField']['No']] // Установка текстового значения

                    ]
                ]
            ],
            'request_id' => uniqid(), // Уникальный идентификатор запроса
        ];

        return $this->amoRequestService->makeRequest('PATCH', $url, $this->accessToken, $data);
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