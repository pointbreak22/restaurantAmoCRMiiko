<?php

namespace App\Controller;


//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;

use App\DTO\HookDataDTO;
use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\AmoCRM\AmoNoteService;
use App\Service\AmoCRM\SetContactService;
use App\Service\AmoCRM\WebHookService;
use App\Service\IikoTableReservationService;
use Exception;
use Random\RandomException;

class WebhookController extends Controller
{
    private WebhookService $webhookService;
    private AmoAuthService $amoAuthService;

    private SetContactService $setContactService;
    private IikoTableReservationService $ikoTableReservationService;

    private AmoNoteService $amoNoteService;


    private AmoLeadService $amoLeadService;

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
            $this->amoLeadService = new AmoLeadService($accessToken);
            $this->amoNoteService = new AmoNoteService($accessToken);
            $result = $this->amoLeadService->doHookData($leadID, $hookDataDTO);

//            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод: " . print_r($result, true));
//
//            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод: " . print_r($hookDataDTO, true));


            if (!empty($hookDataDTO->getIdReserve())) {
                // $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:установлен ид резерва");
                //   $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($resultNode, true));
                //    http_response_code(200);

                $this->response()->send(
                    json_encode(['status' => 'success']),
                    200,
                    ['Content-Type: application/json'],
                );

                //  throw new Exception("Приход лишнего хука " . print_r($_POST, true));

                exit;

            }


            if (empty($hookDataDTO->getCountPeople())) {
                $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: количество людей не установлено");
                throw new Exception("Статус ошибка: количество людей не установлено");

            }

            if (empty($hookDataDTO->getDataReserve()) || empty($hookDataDTO->getTimeReserve())) {
                $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:  дата или время резерва не установлено");
                throw new Exception("Статус ошибка:  дата или время резерва не установлено");
//
            }


            if (empty($hookDataDTO->getNameReserve())) {
                $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:название  резерва не установлено");
                throw new Exception("Статус ошибка:название  резерва не установлено");

//
            }

            if (isset($result['httpCode']) && $result['httpCode'] >= 400) {
                $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
                throw new Exception("Ошибка: " . print_r($result, true));

            }

            //---------------------------------------------------------------

            if ($hookDataDTO->isCreatedReserve()) {


                // создание резерва
                $result = $this->ikoTableReservationService->execute($hookDataDTO);

                if ($result["httpCode"] == 200) {

                    if (empty($result['response']['reserveInfo']['errorInfo'])) {
                        $idReserve = $result['response']['reserveInfo']['id'];

                        //если успех, то изменяет поле
                        $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус успех. Резерв создан на рассмотрение " . $idReserve);

                        if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                            throw new Exception("Ошибка: " . print_r($resultNode, true));

                        }

                        $resultNode = $this->amoNoteService->editReserveInfo($hookDataDTO->getLeadId(), $idReserve);
                        if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                            throw new Exception("Ошибка: " . print_r($resultNode, true));
                        }
                    } else {
                        $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result, true));
                        if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                            throw new Exception("Ошибка: " . print_r($resultNode, true));

                        }
                    }

                } else {

                    $errorMessage = "";

                    if (isset($result['response']['errorDescription'])) {
                        $errorMessage = $result['response']['errorDescription'];
                    } elseif (isset($result['response']['message'])) {
                        $errorMessage = $result['response']['message'];
                    } else {
                        $errorMessage = print_r($result['response'], true);
                    }

                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Ошибка IIKO, статус " . $result["httpCode"] . " ошибка " . $errorMessage);
                    if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                        throw new Exception("Ошибка: " . print_r($resultNode, true));

                    }
                }


            } else {
                $resultNode = "отключено создание резерва";
                throw new Exception("Ошибка: " . print_r($resultNode, true));


            }
            //  $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($_POST, true));

            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Вывод: конец выполнение хука");


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