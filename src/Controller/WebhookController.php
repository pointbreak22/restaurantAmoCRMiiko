<?php

namespace App\Controller;


//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\AmoCRM\AmoNoteService;
use App\Service\AmoCRM\SetContactService;
use App\Service\AmoCRM\WebHookService;
use App\Service\IikoTableReservationService;
use Random\RandomException;

class WebhookController extends Controller
{
    private WebhookService $webhookService;
    private AmoAuthService $amoAuthService;

    private SetContactService $getContactService;
    private IikoTableReservationService $ikoTableReservationService;

    private AmoNoteService $amoNoteService;

    function __construct()
    {
        $this->webhookService = new WebhookService();
        $this->amoAuthService = new AmoAuthService();
        $this->ikoTableReservationService = new IikoTableReservationService();
        $this->amoNoteService = new AmoNoteService();


    }

    /**
     * @throws RandomException
     */
    public function handleWebhook()
    {
        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result 09123----------- ");

        $hookData = $this->webhookService->startProcessing();
        $accessToken = $this->amoAuthService->initializeToken();
        $this->getContactService = new SetContactService($accessToken);
        $hookData = $this->getContactService->setContactsByLead($hookData);
        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result 09123----------- " . print_r($hookData, true));

        // Создаем объект DateTime из timestamp
        $timestamp = $hookData['dataReserveField'];


        $date = \DateTime::createFromFormat('U.u', $timestamp . '.0');

        // Форматируем в нужный вид
        $formattedDate = $date->format('Y-m-d H:i:s.v');
        // $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($formattedDate, true));
        $name = $hookData['name'];
        $email = $hookData['Email'];
        $phone = $hookData['Phone'];
        $nameReserve = $hookData['nameReserveField'];
        $countPeople = $hookData['countPeopleField'];
        $timeMinutes = $hookData['timeField'];
        $result = $this->ikoTableReservationService->execute($name, $email, $phone, $formattedDate, $countPeople, $timeMinutes, $nameReserve);

        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result22 ----------- " . print_r($result, true));
        // $resultNode = $this->amoNoteService->addNoteToLead($hookData['leadId'], json_encode($result));

        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result22 ----------- " . print_r($result, true));


    }
}