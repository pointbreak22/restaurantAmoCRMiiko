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
        $lead = $this->getLeadById($hookDataDTO->getLeadId());
        // return $lead;  //получить список полей в сделке

        // Проверяем, есть ли контакты в сделке
        if (isset($lead['_embedded']['contacts']) && count($lead['_embedded']['contacts']) > 0) {
            $contactId = $lead['_embedded']['contacts'][0]['id']; // Получаем все ID контактов

            $contactInfo = $this->getContactsByIds($contactId); // Получаем подробности о контактах
            if (isset($contactInfo['custom_fields_values']) && count($contactInfo['custom_fields_values']) > 0) {

                return $this->setContactsByDataLead($hookDataDTO, $contactInfo);

            } else {

                return "No found in contacts phone or email.";
            }


        } else {
            return "No contacts found for this lead.";
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