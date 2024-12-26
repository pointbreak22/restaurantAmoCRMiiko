<?php

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoCheckLeadService;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\AmoCRM\AmoUpdateLeadService;
use App\Service\IikoTableReservationService;
use App\Service\LoggingService;
use Exception;

class WebhookController extends Controller
{
    private IikoTableReservationService $ikoTableReservationService;
    private AmoLeadService $amoLeadService;
    private AmoCheckLeadService $amoCheckLeadService;

    private AmoUpdateLeadService $amoUpdateLeadService;

    function __construct()
    {
        $this->ikoTableReservationService = new IikoTableReservationService();
        $this->amoLeadService = new AmoLeadService();
        $this->amoCheckLeadService = new AmoCheckLeadService();
        $this->amoUpdateLeadService = new AmoUpdateLeadService();
    }

    /**
     * todo: Изменить HookDataDTO (переименовать, формировать его в сервисе или репозитории)
     * todo: Проверки условий заполненности полей вынести в отдельный сервис
     * todo: Создание бронирования и проверку ответа убрать из контроллера
     */
    public function handleWebhook(): void
    {
        try {
            $data = $_POST;

            $leadDTO = $this->amoLeadService->getLeadDTO($data);
            $this->amoCheckLeadService->checkDTO($leadDTO);
            $result = $this->ikoTableReservationService->execute($leadDTO);
            $this->amoUpdateLeadService->sendMessageLead($result, $leadDTO->getLeadId());


        } catch (Exception $exception) {


            LoggingService::save($exception->getMessage(), "Error", "webhook");
        } finally {
            $this->response()->send(
                json_encode(['status' => 'success']),
                200,
                ['Content-Type: application/json'],
            );
        }
    }
}