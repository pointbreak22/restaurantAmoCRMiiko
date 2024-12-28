<?php

namespace App\Service\AmoCRM;

use App\DTO\LeadDTO;
use Exception;

class AmoCheckLeadService
{

    /**
     * @throws Exception
     */

    public function checkCreatedReserve(LeadDTO $leadDTO): void
    {
        if (!$leadDTO->isCreatedReserve()) {
            exit;
        }
    }

    /**
     * @throws Exception
     */
    public function checkCountPeople(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getCountPeople())) {
            throw new Exception('количество людей не установлено');
        }

    }

    /**
     * @throws Exception
     */
    public function checkNameReserve(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getNameReserve())) {
            throw new Exception('название  резерва не установлено');
        }
    }


    /**
     * @throws Exception
     */
    public function checkDateReserve(LeadDTO $leadDTO): void
    {
        if (empty($leadDTO->getDataReserve()) || empty($leadDTO->getTimeReserve())) {
            throw new Exception('дата или время резерва не установлено');
        }

    }


    public function checkIdReserve(LeadDTO $leadDTO): void
    {
        if (!empty($leadDTO->getIdReserve())) {
            exit;
        }
    }

}