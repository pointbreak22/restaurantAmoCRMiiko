<?php

namespace App\Service\AmoCRM;

use App\Service\LoggingService;
use DateTime;
use Exception;

class AmoLeadService
{


    private string $baseUrl;
    private AmoRequestService $amoRequestService;

    function __construct()
    {
        $this->amoRequestService = new AmoRequestService();
        $this->baseUrl = "https://" . AMO_DOMAIN;

    }

    /**
     * @throws Exception
     */
    public function doHookData($leadId, $hookDataDTO): void
    {
        $leadResponse = $this->getLeadById($leadId);
        LoggingService::save($leadResponse, "info", "webhook");
        $leadArray = $leadResponse['data']['custom_fields_values'];
        $this->writeLeadToHookData($leadArray, $hookDataDTO);
        $contactId = $leadResponse['data']['_embedded']['contacts'][0]['id']; // Получаем все ID контактов
        $contactResponse = $this->getContactsByIds($contactId); // Получаем подробности о контактах
        $contactName = $contactResponse['data']['name'];
        $hookDataDTO->setContactName($contactName);
        $contactArray = $contactResponse['data']['custom_fields_values'];
        $this->writeContactToHookData($contactArray, $hookDataDTO);
    }

    /**
     * @throws Exception
     */
    private function writeLeadToHookData($data, $hookDataDTO): void
    {
        try {
            $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];
            $createdReserve = $this->getCreatedReserve($data, $amoFieldsConfig['createReserveField']);
            $dateReserve = $this->getValueReserve($data, $amoFieldsConfig['dataReserveField']);
            $timeReserve = $this->getValueReserve($data, $amoFieldsConfig['timeReserveField']);
            $countPeople = $this->getValueReserve($data, $amoFieldsConfig['countPeopleField']);
            $nameReserve = $this->getValueReserve($data, $amoFieldsConfig['nameReserveField']);
            $IdReserve = $this->getValueReserve($data, $amoFieldsConfig['idReserveField']);
            $sumReserve = $this->getValueReserve($data, $amoFieldsConfig['sumReserveField']);

            if (!empty($dateReserve) && !empty($timeReserve)) {
                $date = DateTime::createFromFormat('U.u', $dateReserve . '.0');//->setTime(0, 0, 0);
                $pattern = '/с (\d{1,2}:\d{2}) до (\d{1,2}:\d{2})/';
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
            $hookDataDTO->setSumReserve($sumReserve);

        } catch (Exception $e) {
            LoggingService::save($hookDataDTO->getMessage(), "Error", "webhook");
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
            LoggingService::save($hookDataDTO->getMessage(), "Error", "webhook");
        }
    }

    /**
     * @throws Exception
     */
    private function getLeadById($leadId): array
    {
        $url = "{$this->baseUrl}/api/v4/leads/{$leadId}?with=contacts";
        return $this->amoRequestService->makeRequest('GET', $url);
    }

    /**
     * @throws Exception
     */
    private function getContactsByIds($contactId): array
    {
        $url = "{$this->baseUrl}/api/v4/contacts/{$contactId}";
        return $this->amoRequestService->makeRequest('GET', $url);
    }

    /**
     * @throws Exception
     */
    public function addNoteToLead(int $leadId, string $text): void
    {
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
        $this->amoRequestService->makeRequest('POST', $url, $data);
    }

    /**
     * @throws Exception
     */
    public function editReserveInfo(int $leadId, string $value): void
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
        $this->amoRequestService->makeRequest('PATCH', $url, $data);
    }

    /**
     * @throws Exception
     */
    public function editCreatedReserveInfo(int $leadId): void
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

        $this->amoRequestService->makeRequest('PATCH', $url, $data);
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

    private function getValueReserve($data, mixed $inputField): mixed
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

}