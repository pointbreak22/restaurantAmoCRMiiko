<?php

namespace App\Service\AmoCRM;

use App\DTO\HookDataDTO;
use League\OAuth2\Client\Token\AccessToken;

class SetContactService
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
     * Получение контактов, привязанных к сделке
     */
    public function setContactsByLead(HookDataDTO $hookDataDTO)
    {

        // Получаем сделку по ID
        $leadResponse = $this->getLeadById($hookDataDTO->getLeadId());


        if ($leadResponse['httpCode'] >= 400) {
            return $leadResponse;

        }


        //   return $lead;  //получить список полей в сделке

        // Проверяем, есть ли контакты в сделке
        if (isset($leadResponse['response']['_embedded']['contacts']) && count($leadResponse['response']['_embedded']['contacts']) > 0) {
            $contactId = $leadResponse['response']['_embedded']['contacts'][0]['id']; // Получаем все ID контактов

            $contactResponse = $this->getContactsByIds($contactId); // Получаем подробности о контактах

            if ($contactResponse['httpCode'] >= 400) {
                return $contactResponse;

            }

            if (isset($contactResponse['response']['custom_fields_values']) && count($contactResponse['response']['custom_fields_values']) > 0) {

                return ['httpCode' => 200, 'response' => $this->setContactsByDataLead($hookDataDTO, $contactResponse['response'])];

            } else {
                return ['httpCode' => 400, 'response' => "No found in contacts phone or email."];

            }


        } else {
            return ['httpCode' => 400, 'response' => "No contacts found for this lead."];

        }
    }

    /**
     * Получение сделки по ID
     */
    public function getLeadById($leadId)
    {
        $url = "{$this->baseUrl}/api/v4/leads/{$leadId}?with=contacts";

        $response = $this->amoRequestService->makeRequest('GET', $url, $this->accessToken);

        return $response;
    }

    private function getContactsByIds($contactId)
    {
        // $ids = implode(',', $contactIds); // Формируем строку с ID через запятую
        $url = "{$this->baseUrl}/api/v4/contacts/{$contactId}";

        $response = $this->amoRequestService->makeRequest('GET', $url, $this->accessToken);

        return $response;
    }


    private function setContactsByDataLead(HookDataDTO $hookDataDTO, $contactInfo)
    {
        try {
            $hookDataDTO->setContactName($contactInfo['name']);
            foreach ($contactInfo['custom_fields_values'] as $item) {
                //   $hookData[$item['id']] = $item['values'];

                if ($item['field_code'] == 'EMAIL') {
                    $hookDataDTO->setContactEmail($item['values'][0]['value']);

                }
                if ($item['field_code'] == 'PHONE') {
                    $hookDataDTO->setContactPhone($item['values'][0]['value']);

                }
            }
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }


    }


}