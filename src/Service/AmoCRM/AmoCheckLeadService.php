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
            throw new Exception('количество людей не установлено');
        }

    }

    /**
     * @throws Exception
     */
    private function checkNameReserve(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getNameReserve())) {
            throw new Exception('название  резерва не установлено');
        }
    }


    /**
     * @throws Exception
     */
    private function checkDateReserve(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getDataReserve()) || empty($leadDTO->getTimeReserve())) {
            throw new Exception('дата или время резерва не установлено');
        }

    }


    private function checkIdReserve(LeadDTO $leadDTO): void
    {
        if (!empty($leadDTO->getIdReserve())) {
            exit;
        }
    }

}