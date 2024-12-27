<?php

namespace App\Service\AmoCRM;

use Exception;

class AmoCommentService
{

    private AmoLeadService $amoLeadService;

    public function __construct()
    {
        $this->amoLeadService = new AmoLeadService();
    }

    /**
     * @throws Exception
     */
    public function execute(string $leadId, string $message, string $type = ''): void
    {
        switch ($type) {
            case 'success':
                $this->amoLeadService->addNoteToLead($leadId, 'Статус "Успех": ' . $message);
                break;
            case 'error':
                $this->amoLeadService->addNoteToLead($leadId, 'Статус "Ошибка": ' . $message);
                break;
        }
    }
}