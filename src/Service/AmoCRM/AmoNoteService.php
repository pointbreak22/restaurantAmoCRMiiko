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
    public function addNoteToLead(int $leadId, string $text)
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
                ]
            ]
        ];

        // Отправка POST-запроса
        $response = $this->amoRequestService->makeRequest('POST', $url, $this->accessToken, $data);

        return $response;
    }
}