<?php

namespace App\Tests;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoRequestService;

class AmoCrmTokenTest extends Controller
{
    private AmoRequestService $amoRequestService;

    function __construct()
    {
        $this->amoRequestService = new AmoRequestService();
    }

    /**
     * @throws \Exception
     */
    public function index(): void
    {
        $url = 'https://' . AMO_DOMAIN . "/api/v4/account";
        $result = $this->amoRequestService->makeRequest('GET', $url);
        dd($result);

    }
}