<?php

namespace App\Service\AmoCRM;

use League\OAuth2\Client\Token\AccessToken;

class AmoNoteService
{
    private AccessToken $accessToken;
    private string $baseUrl;

    private AmoRequestService $amoRequestService;

    public function __construct($accessToken)
    {
        $this->amoRequestService = new AmoRequestService();
        $this->accessToken = $accessToken;
        $this->baseUrl = "https://{$accessToken->getValues()['baseDomain']}";
    }

    /**
     * Добавление примечания в сделку
     * @param int $leadId
     * @param string $text
     * @return mixed
     */
    public function addNoteToLead(int $leadId, string $text): mixed
    {
//        $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];


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
        //
        return $this->amoRequestService->makeRequest('POST', $url, $this->accessToken, $data);
    }

    public function editReserveInfo(int $leadId, string $value): mixed
    {
        $amoFieldsConfig = (include APP_PATH . '/config/amo/values.php')[APP_ENV]['custom_fields'];

        $url2 = "{$this->baseUrl}/api/v4/leads/{$leadId}";
        $data2 = [
            'custom_fields_values' => [
                [
                    'field_id' => (int)$amoFieldsConfig['idReserveField'],
                    'values' => [
                        ['value' => (string)$value] // Установка текстового значения

                    ]
                ]
            ]
        ];
        // return $data2;
        return $this->amoRequestService->makeRequest('PATCH', $url2, $this->accessToken, $data2);


    }

    public function getleads()
    {
        $url = "{$this->baseUrl}/api/v4/leads/metadata";
        $response = $this->amoRequestService->makeRequest('GET', $url, $this->accessToken);

        return $response;

    }


}