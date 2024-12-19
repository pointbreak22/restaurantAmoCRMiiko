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
//
//        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($hookDataDTO, true));
//        return;


        $accessToken = $this->amoAuthService->initializeToken(true);
        if (!empty($hookDataDTO->getIdReserve())) {
            //       $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Резерв изначально была создан " . $hookDataDTO->getIdReserve());
//$this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Предупреждение: " . print_r("сделка создана", true));

            //    http_response_code(403);
            return;

        }
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


        $this->amoNoteService = new AmoNoteService($accessToken);

        $this->getContactService = new SetContactService($accessToken);
        $result = $this->getContactService->setContactsByLead($hookDataDTO);
        //получить поля в сделке
        // $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
        //return;


        //  $resultNode = $this->amoNoteService->getleads();
//        $resultNode = $this->amoNoteService->editReserveInfo($hookDataDTO->getLeadId(), "eeeeeee");
//
//        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Get fields2 " . print_r($resultNode, true));


//        return;
        if (empty($hookDataDTO->getCountPeople())) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error, количество людей не установлено");
            return;
        }

        if (empty($hookDataDTO->getDataReserve())) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error, дата резерва не установлена");
            return;
        }

        if (empty($hookDataDTO->getTimeReserve())) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error, время резерва не установлена");
            return;

        }

        if (empty($hookDataDTO->getNameReserve())) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error, название  резерва не установлена");
            return;

        }


        if ($result !== true) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
            return;
        }
        if ($hookDataDTO->isCreatedReserve() && empty($hookDataDTO->getIdReserve())) {
            $result = $this->ikoTableReservationService->execute($hookDataDTO);
            //   $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result " . print_r($result, true));


            if ($result["status"] == 200) {

                if (empty($result['data']['reserveInfo']['errorInfo'])) {
                    $idReserve = $result['data']['reserveInfo']['id'];
                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статут успех. Резерв создан на рассмотрение " . $idReserve);


                    $resultNode = $this->amoNoteService->editReserveInfo($hookDataDTO->getLeadId(), $idReserve);


                } else {
                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result['data']['reserveInfo']['errorInfo']['message'], true));

                }

            } else {
                $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result, true));

            }

            //  $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), json_encode($result));
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Статус ошибка " . print_r($resultNode, true));


        } else {
            $resultNode = "отключено создание резерва";
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Статус предупреждение: " . print_r($resultNode, true));


        }


    }
}