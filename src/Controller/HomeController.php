<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\IIKO\IikoDataService;
use App\Service\IIKO\IikoTokenService;
use JetBrains\PhpStorm\NoReturn;

class HomeController extends Controller
{
    // private IikoConnectService $iikoConnectService;

    private IikoTokenService $tokenService;
    private IikoDataService $dataService;

    function __construct()
    {
        //   $this->iikoConnectService=new IikoConnectService();
        $this->tokenService = new IikoTokenService();
        $this->dataService = new IikoDataService();
    }

    #[NoReturn] public function index(): void
    {

        $token = $this->dataService->getIikoOrganization();
        dd($token);

    }
}