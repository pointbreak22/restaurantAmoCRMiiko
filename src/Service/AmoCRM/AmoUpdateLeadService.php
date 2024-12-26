<?php

namespace App\Service\AmoCRM;

class AmoUpdateLeadService
{

    private AmoLeadService $amoLeadService;

    public function __construct()
    {
        $this->amoLeadService = new AmoLeadService();
    }

    /**
     * @throws \Exception
     */
    public function sendMessageLead(array $result, string $leadId): void
    {

        if (empty($result['reserves'][0]['errorInfo'])) {
            $idReserve = $result['reserves'][0]['id'];
            //если успех, то изменяет поле
            $this->amoLeadService->addNoteToLead($leadId, "Статус успех. Резерв на рассмотрении " . print_r($idReserve, true));
            $this->amoLeadService->editReserveInfo($leadId, $idReserve);

        } else {
            throw new \Exception($result['reserves'][0]['errorInfo']);
        }


    }


}