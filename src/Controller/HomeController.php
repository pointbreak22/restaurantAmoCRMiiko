<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\HookDataDTO;
use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\IikoTableReservationService;
use Random\RandomException;


class HomeController extends Controller
{
    private IikoTableReservationService $reservationService;
    private AmoAuthService $amoAuthService;

    function __construct()
    {
        $this->reservationService = new IikoTableReservationService();
        $this->amoAuthService = new  AmoAuthService();
    }

    /**
     * @throws RandomException
     */
    public function index(): void
    {
        $result = $this->amoAuthService->initializeToken();

    }

    public function iiko(): void
    {
        $hookDataDTO = new HookDataDTO();
        $hookDataDTO->setDataReserve('2024-12-20 14:15:22.123',);
        $hookDataDTO->setTimeReserve('180');
        $hookDataDTO->setCountPeople('4');
        $hookDataDTO->setNameReserve("Знахарь");
        //  $hookDataDTO->setCreatedReserve($createdReserve);
        //$hookDataDTO->setIdReserve($IdReserve);

        $hookDataDTO->setContactEmail('zzzzzzzz@mail.ru');
        $hookDataDTO->setContactName('DEEDEDDEEDEe');
        $hookDataDTO->setContactPhone('+77774444545545');

        $ikoTableReservationService = new IikoTableReservationService();
        $result = $ikoTableReservationService->execute($hookDataDTO);
        dd($result);


    }

    public function handleCallback(): void
    {

        $result = $this->amoAuthService->callback();

        //  dd($result);
    }
}