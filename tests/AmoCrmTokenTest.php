<?php

namespace App\Tests;


//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\AmoCRM\AmoRequestService;
use App\Service\AmoCRM\WebHookService;
use App\Service\IikoTableReservationService;

//use App\Service\AmoCRM\AmoNoteService;


class AmoCrmTokenTest extends Controller
{
    private WebhookService $webhookService;
    private AmoAuthService $amoAuthService;

    private AmoRequestService $amoRequestService;


    private IikoTableReservationService $ikoTableReservationService;


    function __construct()
    {
        $this->webhookService = new WebhookService();
        $this->amoAuthService = new AmoAuthService();

        $this->ikoTableReservationService = new IikoTableReservationService();

        $this->amoRequestService = new AmoRequestService();
    }

    /**
     */
    public function handleWebhook(): void
    {

        //  dd("sddssd");

        $url = 'https://' . BASE_DOMAIN . "/api/v4/account";
        $result = $this->amoRequestService->makeRequest('GET', $url);

        //  $method = '/oauth2/access_token';
        //    $result = $this->amoRequestService->makeRequest("GET", "https://" . BASE_DOMAIN . $method);

        dd($result);


    }
}