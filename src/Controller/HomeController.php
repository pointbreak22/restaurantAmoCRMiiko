<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\IikoConnectService;
use JetBrains\PhpStorm\NoReturn;

class HomeController extends Controller
{
    private IikoConnectService $iikoConnectService;

    function __construct()
    {
        $this->iikoConnectService=new IikoConnectService();
    }
    #[NoReturn] public function index(): void
    {
     //   $this->view('home');
        $result=$this->iikoConnectService->testResponse();
        dd($result);


    }
}