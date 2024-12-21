<?php

namespace App\Controller;


//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;

use App\DTO\HookDataDTO;
use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\AmoCRM\AmoNoteService;
use App\Service\AmoCRM\WebHookService;
use App\Service\IikoTableReservationService;
use Exception;
use Random\RandomException;

class WebhookController extends Controller
{
    private WebhookService $webhookService;
    private AmoAuthService $amoAuthService;


    private IikoTableReservationService $ikoTableReservationService;


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

// Проверка уникального идентификатора

        try {
//            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($_POST, true));
//
//            return;


            $this->response()->send(
                json_encode(['status' => 'success']),
                200,
                ['Content-Type: application/json'],
            );
            $hookDataDTO = new HookDataDTO();


            $leadID = $this->webhookService->getLeadId();
            if (empty($leadID)) {
                throw new Exception("Статус ошибка: отсутствует id сделки");

            }
            $hookDataDTO->setLeadId($leadID);


            //получение токена
            $accessToken = $this->amoAuthService->initializeToken(true);

            if (!isset($accessToken)) {


                $this->response()->send(
                    json_encode(['status' => 'Требуется авторизация']),
                    401,
                    ['Content-Type: application/json'],
                );

                throw new Exception("Статус ошибка: отсутствует авторизация");
            }
            $amoLeadService = new AmoLeadService($accessToken);
            $amoNoteService = new AmoNoteService($accessToken);
            $result = $amoLeadService->doHookData($leadID, $hookDataDTO);
//            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод полей сделки" . print_r($result, true));
//            return;
            if (isset($result['status']) && $result['status'] >= 400) {
                $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
                $resultNode = $amoNoteService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Ошибка: " . print_r($result, true));

            }

            if (!$hookDataDTO->isCreatedReserve()) {
                $this->response()->send(
                    json_encode(['status' => 'success']),
                    200,
                    ['Content-Type: application/json'],
                );
                //  throw new Exception("Приход лишнего хука " . print_r($_POST, true));
                exit;
            }

            if (empty($hookDataDTO->getCountPeople())) {
                $resultNode = $amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: количество людей не установлено");
                $resultNode = $amoNoteService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Статус ошибка: количество людей не установлено");

            }

            if (empty($hookDataDTO->getDataReserve()) || empty($hookDataDTO->getTimeReserve())) {
                $resultNode = $amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:  дата или время резерва не установлено");
                $resultNode = $amoNoteService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Статус ошибка:  дата или время резерва не установлено");

            }

            if (empty($hookDataDTO->getNameReserve())) {
                $resultNode = $amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:название  резерва не установлено");
                $resultNode = $amoNoteService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Статус ошибка:название  резерва не установлено");

            }

            if (!empty($hookDataDTO->getIdReserve())) {

                $this->response()->send(
                    json_encode(['status' => 'success']),
                    200,
                    ['Content-Type: application/json'],
                );

                //  throw new Exception("Приход лишнего хука " . print_r($_POST, true));
                exit;

            }

            $result = $this->ikoTableReservationService->execute($hookDataDTO);

            if ($result['status'] == 200) {

                if (empty($result['data']['reserveInfo']['errorInfo'])) {
                    $idReserve = $result['data']['reserveInfo']['id'];  //$idReserve

                    //если успех, то изменяет поле
                    $resultNode = $amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус успех. Резерв на рассмотрении " . $idReserve);

                    if (isset($resultNode['status']) && $resultNode['status'] >= 400) {
                        throw new Exception("Ошибка: " . print_r($resultNode, true));

                    }

                    $resultNode = $amoNoteService->editReserveInfo($hookDataDTO->getLeadId(), $idReserve);
                    if (isset($resultNode['status']) && $resultNode['status'] >= 400) {
                        throw new Exception("Ошибка: " . print_r($resultNode, true));
                    }
                } else {
                    $resultNode = $amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result['data']['reserveInfo']['errorInfo']['message'], true));

                    if (isset($resultNode['status']) && $resultNode['status'] >= 400) {
                        throw new Exception("Ошибка: " . print_r($resultNode, true));

                    }
                    $resultNode = $amoNoteService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                }

            } else {

                $errorMessage = "";

                if (isset($result['data']['errorDescription'])) {
                    $errorMessage = $result['data']['errorDescription'];
                } elseif (isset($result['data']['message'])) {
                    $errorMessage = $result['data']['message'];
                } else {
                    $errorMessage = print_r($result, true);
                }

                $resultNode = $amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Ошибка IIKO, статус " . $result['status'] . " ошибка " . $errorMessage);
                if (isset($resultNode['status']) && $resultNode['status'] >= 400) {
                    throw new Exception("Ошибка: " . print_r($resultNode, true));

                }
                $resultNode = $amoNoteService->editCreatedReserveInfo($hookDataDTO->getLeadId());
            }


            //   $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод: конец выполнение хука");


            $this->response()->send(
                json_encode(['status' => 'success']),
                200,
                ['Content-Type: application/json'],
            );
        } catch (Exception $exception) {
            $this->response()->send(
                json_encode(['status' => 'success']),
                200,
                ['Content-Type: application/json'],
            );
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Статус ошибка: " . $exception->getMessage());
            exit;
        }

    }
}