<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\IIKO\Core\IikoTokenService;
use App\Service\IikoTableReservationService;

class HomeController extends Controller
{
    private IikoTableReservationService $reservationService;

    function __construct()
    {
        $this->reservationService = new IikoTableReservationService();
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
}