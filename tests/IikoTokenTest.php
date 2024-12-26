<?php

namespace App\Tests;


use App\Kernel\Controller\Controller;
use App\Service\IIKO\Core\IikoTokenService;

class IikoTokenTest extends Controller
{

    private IikoTokenService $tokenService;

    public function __construct()
    {
        $this->tokenService = new IikoTokenService();
    }

    /**
     * @throws \Exception
     */
    public function index(): void
    {
        $result = $this->tokenService->getNewToken();
        dd($result);
    }


}