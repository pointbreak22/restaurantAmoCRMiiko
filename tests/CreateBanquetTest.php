<?php

namespace App\Tests;

use App\DTO\LeadDTO;
use App\Service\IikoTableReservationService;

class CreateBanquetTest extends \App\Kernel\Controller\Controller
{


    public function index(): mixed
    {

        $hookDataDTO = new LeadDTO();
        $hookDataDTO->setLeadId('30377193');
        $hookDataDTO->setDataReserve('2024-12-28 14:15:22.123');
        $hookDataDTO->setTimeReserve('180');
        $hookDataDTO->setCountPeople('4');
        $hookDataDTO->setNameReserve("Знахарь");
        $hookDataDTO->setContactName('DEEDEDDEEDEe');
        $hookDataDTO->setContactPhone('998765423332');
        $hookDataDTO->setSumReserve(2.5);
        $hookDataDTO->setCreatedReserve(true);

        return $hookDataDTO;
        $ikoTableReservationService = new IikoTableReservationService();
        $result = $ikoTableReservationService->execute($hookDataDTO);
        dd($result);

    }
}