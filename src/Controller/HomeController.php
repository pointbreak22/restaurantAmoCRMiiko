<?php

declare(strict_types=1);

namespace App\Controller;

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

    public function handleCallback(): void
    {

        $result = $this->amoAuthService->callback();

        //  dd($result);
    }
}