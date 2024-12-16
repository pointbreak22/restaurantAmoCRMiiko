<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\IIKO\Core\IikoTokenService;
use App\Service\IikoTableReservationService;


class HomeController extends Controller
{
    private IikoTableReservationService $reservationService;
    private AmoAuthService $amoAuthService;

    function __construct()
    {
        $this->reservationService = new IikoTableReservationService();

        $this->amoAuthService = new  AmoAuthService();
    }

    public function index(): void
    {
        $tableReservationId = $this->reservationService->execute();
        dd($tableReservationId);
    }

    public function testToken()
    {
        $tokenService = new IikoTokenService();
        $token = $tokenService->getToken();
        dd([
            '$token' => $token
        ]);
    }

    public function testAmoCrm(): void
    {
        $this->amoAuthService->initializeProvider();
        //    $amoAuthService = new  AmoAuthService();
        // $amoAuthService->init();

    }

    public function handleCallback(): void
    {
        $result = $this->amoAuthService->callback();

        dd($result);
    }
}