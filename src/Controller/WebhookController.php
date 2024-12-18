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


    }

    /**
     * @throws RandomException
     */
    public function handleWebhook()
    {

        $hookDataDTO = $this->webhookService->startProcessing();

        if ($hookDataDTO->isCreatedReserveInfo()) {
            http_response_code(403);

            echo json_encode([
                'error' => 'Forbidden',
                'message' => 'You do not send reserve.'
            ]);
            return;

        }
        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($hookDataDTO, true));


        $accessToken = $this->amoAuthService->initializeToken(true);

        if (!isset($accessToken)) {
            //    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Вы не авторизованны на сервере, чтоб авторизоваться перейдите по ссылке: " . HOST_SERVER);
            //  $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($resultNode, true));
            http_response_code(401);
            echo json_encode([
                'error' => 'Forbidden',
                'message' => 'You do not have the necessary permissions to access this resource.'
            ]);

            return;
        }

        $this->getContactService = new SetContactService($accessToken);
        $result = $this->getContactService->setContactsByLead($hookDataDTO);
//        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
//        return;

     
        if ($result !== true) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
            return;
        }
        if ($hookDataDTO->isCreatedReserve()) {
            $result = $this->ikoTableReservationService->execute($hookDataDTO);
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result1--------- " . print_r($result, true));

            $this->amoNoteService = new AmoNoteService($accessToken);

            if ($result["status"] == 200) {

                if (empty($result['data']['reserveInfo']['errorInfo'])) {
                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Сделка создана на рассмотрение");

                } else {
                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Ошибка создания резерва: " . print_r($result['data']['reserveInfo']['errorInfo'], true));

                }

            } else {
                $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Ошибка: " . print_r($result, true));

            }

            //  $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), json_encode($result));
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($resultNode, true));


        } else {
            $resultNode = "отключено создание резерва";
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($resultNode, true));


        }


    }
}