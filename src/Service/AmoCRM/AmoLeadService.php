<?php

namespace App\Service\AmoCRM;

use App\DTO\LeadDTO;
use App\Service\AmoCRM\Core\AmoHttpClient;
use App\Service\LoggingService;
use DateTime;
use Exception;

class AmoLeadService
{

    private string $baseUrl;
    private AmoHttpClient $amoRequestService;

    function __construct()
    {
        $this->amoRequestService = new AmoHttpClient();
        $this->baseUrl = "https://" . AMO_DOMAIN;

    }

    /**
     * @throws Exception
     */
    public function getLeadDTO($data): LeadDTO
    {
        $leadDTO = new LeadDTO();
        $leadId = $this->getLeadID($data);
        $leadDTO->setLeadId($leadId);

        $leadResponse = $this->getLeadById($leadId);
        //LoggingService::save($leadResponse, "info", "webhook"); //нужно для логирования данных сделки

        $leadArray = $leadResponse['custom_fields_values'];
        $this->writeLeadToHookData($leadArray, $leadDTO);
        $contactId = $leadResponse['_embedded']['contacts'][0]['id']; // Получаем все ID контактов
        $contactResponse = $this->getContactsByIds($contactId); // Получаем подробности о контактах
        $contactName = $contactResponse['name'];
        $leadDTO->setContactName($contactName);
        $contactArray = $contactResponse['custom_fields_values'];
        $this->writeContactToHookData($contactArray, $leadDTO);

        return $leadDTO;
    }

    /**
     * @throws Exception
     */
    private function getLeadID($data): ?string
    {
        // Проверка на наличие данных о лидах
        if (isset($data["leads"]["update"][0]['id'])) {
            $leadID = $data["leads"]["update"][0]['id'];
        } elseif (isset($data["leads"]["add"][0]['id'])) {
            $leadID = $data["leads"]["add"][0]['id'];
        } else {
            throw new Exception("Invalid lead id");
        }
        return $leadID;

    }


    /**
     * @throws Exception
     */
    private function writeLeadToHookData($data, $leadDTO): void
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

                $leadDTO->setDataReserve($startDateTime->format('Y-m-d H:i:s.v'));
                $leadDTO->setTimeReserve($durationInMinutes);
            }

            $leadDTO->setCreatedReserve($createdReserve);
            $leadDTO->setCountPeople($countPeople);
            $leadDTO->setNameReserve($nameReserve);
            $leadDTO->setIdReserve($IdReserve);
            $leadDTO->setSumReserve($sumReserve);

        } catch (Exception $e) {
            LoggingService::save($leadDTO->getMessage(), "Error", "webhook");
        }
    }

    private function writeContactToHookData($data, $leadDTO): void
    {
        try {
            foreach ($data as $item) {
                if ($item['field_code'] == 'EMAIL') {
                    $leadDTO->setContactEmail($item['values'][0]['value']);
                }
                if ($item['field_code'] == 'PHONE') {
                    $leadDTO->setContactPhone($item['values'][0]['value']);
                }
            }
        } catch (Exception $e) {
            LoggingService::save($leadDTO->getMessage(), "Error", "webhook");
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
                "entity_id" => (int)$leadId,
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