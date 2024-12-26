<?php

namespace App\Tests;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\Core\AmoHttpClient;

class AmoCrmTokenTest extends Controller
{
    private AmoHttpClient $amoRequestService;

    function __construct()
    {
        $this->amoRequestService = new AmoHttpClient();
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