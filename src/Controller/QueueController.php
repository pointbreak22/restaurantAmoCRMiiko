<?php

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoCheckLeadService;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\AmoCRM\Core\AmoQueueService;
use App\Service\LoggingService;
use Exception;

class QueueController extends Controller
{
    private AmoQueueService $queueService;
    private AmoLeadService $amoLeadService;

    private AmoCheckLeadService $amoCheckLeadService;

    function __construct()
    {

        $this->queueService = new AmoQueueService();
        $this->amoLeadService = new AmoLeadService();
        $this->amoCheckLeadService = new AmoCheckLeadService();
    }

    public function addToQueue(): void
    {
        try {
            // Проверка статуса приложения
            if (APP_IS_WORK === 'true') {
                // Получение объекта из AmoCRM
                $leadDTO = $this->amoLeadService->getLeadDTO($_POST);

                // Проверка обязательных полей
                $this->amoCheckLeadService->checkCreatedReserve($leadDTO);
                $this->amoCheckLeadService->checkIdReserve($leadDTO);
                // Добавление в очередь
                $this->queueService->create($leadDTO);
            }
        } catch (Exception $exception) {
            LoggingService::save(
                $exception->getMessage(),
                "Error",
                "webhook");
        } finally {
            // Постоянный ответ для AmoCRM
            $this->response()->send(
                json_encode(['status' => 'success']),
                200,
                ['Content-Type: application/json'],
            );
        }
    }
}