<?php

namespace App\Controller;


use App\DTO\HookDataDTO;
use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\IikoTableReservationService;
use App\Service\LoggingService;
use Exception;

//use App\Service\AmoCRM\AmoNoteService;


class WebhookController extends Controller
{


    private IikoTableReservationService $ikoTableReservationService;


    function __construct()
    {


        $this->ikoTableReservationService = new IikoTableReservationService();
    }

    /**
     */
    public function handleWebhook(): void
    {
        try {

            $hookDataDTO = new HookDataDTO();
            $data = $_POST;

            // Проверка на наличие данных о лидах
            if (isset($data["leads"]["update"][0]['id'])) {
                $leadID = $data["leads"]["update"][0]['id'];
            } elseif (isset($data["leads"]["add"][0]['id'])) {
                $leadID = $data["leads"]["add"][0]['id'];
            } else {
                throw new Exception("Invalid lead id");
            }
            $hookDataDTO->setLeadId($leadID);
            $amoLeadService = new AmoLeadService();
            $amoLeadService->doHookData($leadID, $hookDataDTO);

            if (!$hookDataDTO->isCreatedReserve()) {
                $this->response()->send(
                    json_encode(['status' => 'success']),
                    200,
                    ['Content-Type: application/json'],
                );
                exit;
            }

            if (empty($hookDataDTO->getCountPeople())) {
                $amoLeadService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: количество людей не установлено");
                $amoLeadService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Статус ошибка: количество людей не установлено");
            }

            if (empty($hookDataDTO->getNameReserve())) {
                $amoLeadService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:название  резерва не установлено");
                $amoLeadService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Статус ошибка:название  резерва не установлено");
            }

            if (empty($hookDataDTO->getDataReserve()) || empty($hookDataDTO->getTimeReserve())) {
                $amoLeadService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка:  дата или время резерва не установлено");
                $amoLeadService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                throw new Exception("Статус ошибка:  дата или время резерва не установлено--" . $hookDataDTO->getDataReserve() . "--" . $hookDataDTO->getTimeReserve());
            }

            if (!empty($hookDataDTO->getIdReserve())) {
                $this->response()->send(
                    json_encode(['status' => 'success']),
                    200,
                    ['Content-Type: application/json'],
                );
                exit;
            }

            $result = $this->ikoTableReservationService->execute($hookDataDTO);
            if ($result['status'] == 200) {

                if (empty($result['data']['reserveInfo']['errorInfo'])) {
                    $idReserve = $result['data']['reserves'][0]['id'];
                    //если успех, то изменяет поле
                    $amoLeadService->addNoteToLead($hookDataDTO->getLeadId(), "Статус успех. Резерв на рассмотрении " . print_r($result, true));
                    $amoLeadService->editReserveInfo($hookDataDTO->getLeadId(), $idReserve);

                } else {
                    $amoLeadService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result['data']['reserveInfo']['errorInfo']['message'], true));
                    $amoLeadService->editCreatedReserveInfo($hookDataDTO->getLeadId());
                }

            } else {

                if (isset($result['data']['errorDescription'])) {
                    $errorMessage = $result['data']['errorDescription'];
                } elseif (isset($result['data']['message'])) {
                    $errorMessage = $result['data']['message'];
                } else {
                    $errorMessage = print_r($result, true);
                }
                $amoLeadService->addNoteToLead($hookDataDTO->getLeadId(), "Ошибка IIKO, статус " . $result['status'] . " ошибка " . $errorMessage);
                $amoLeadService->editCreatedReserveInfo($hookDataDTO->getLeadId());

            }

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
            LoggingService::save($exception->getMessage(), "Error", "webhook");
            exit;

        }

    }
}