<?php

namespace App\Service\AmoCRM;

use App\DTO\LeadDTO;
use Exception;

class AmoCheckLeadService
{
    private AmoLeadService $amoLeadService;

    public function __construct()
    {
        $this->amoLeadService = new AmoLeadService();
    }

    /**
     * @throws Exception
     */
    public function checkDTO(LeadDTO $leadDTO): void
    {


        $this->checkCreatedReserve($leadDTO);
        $this->checkCountPeople($leadDTO);
        $this->checkNameReserve($leadDTO);
        $this->checkDateReserve($leadDTO);
        $this->checkIdReserve($leadDTO);


    }

    private function checkCreatedReserve(LeadDTO $leadDTO): void
    {
        if (!$leadDTO->isCreatedReserve()) {
            exit;
        }
    }

    /**
     * @throws Exception
     */
    private function checkCountPeople(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getCountPeople())) {
            $this->amoLeadService->addNoteToLead($leadDTO->getLeadId(), "Статус ошибка: количество людей не установлено");
            $this->amoLeadService->disableSync($leadDTO->getLeadId());
            exit;
        }

    }

    /**
     * @throws Exception
     */
    private function checkNameReserve(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getNameReserve())) {
            $this->amoLeadService->addNoteToLead($leadDTO->getLeadId(), "Статус ошибка:название  резерва не установлено");
            $this->amoLeadService->disableSync($leadDTO->getLeadId());
            exit;
        }
    }


    /**
     * @throws Exception
     */
    private function checkDateReserve(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getDataReserve()) || empty($leadDTO->getTimeReserve())) {
            $this->amoLeadService->addNoteToLead($leadDTO->getLeadId(), "Статус ошибка:  дата или время резерва не установлено");
            $this->amoLeadService->disableSync($leadDTO->getLeadId());
            exit;
        }

    }


    private function checkIdReserve(LeadDTO $leadDTO): void
    {
        if (!empty($leadDTO->getIdReserve())) {
            exit;
        }
    }

}