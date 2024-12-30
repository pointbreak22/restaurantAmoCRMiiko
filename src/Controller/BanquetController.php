<?php

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoCheckLeadService;
use App\Service\AmoCRM\AmoCommentService;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\AmoCRM\Core\AmoQueueService;
use App\Service\IikoTableReservationService;
use App\Service\LoggingService;
use Exception;

class BanquetController extends Controller
{
    private AmoQueueService $queueService;
    private IikoTableReservationService $ikoTableReservationService;
    private AmoLeadService $amoLeadService;
    private AmoCheckLeadService $amoCheckLeadService;
    private AmoCommentService $amoUpdateLeadService;

    function __construct()
    {
        $this->queueService = new AmoQueueService();
        $this->ikoTableReservationService = new IikoTableReservationService();
        $this->amoLeadService = new AmoLeadService();
        $this->amoCheckLeadService = new AmoCheckLeadService();
        $this->amoUpdateLeadService = new AmoCommentService();
    }

    public function readQueue(): void
    {
        // Проверка статуса приложения
        if (APP_IS_WORK !== 'true') {
            exit;
        }

        while ($leadDTO = $this->queueService->popFirst()) {
            try {
                $this->amoCheckLeadService->checkNameReserve($leadDTO);
                $this->amoCheckLeadService->checkDateReserve($leadDTO);
                $this->amoCheckLeadService->checkCountPeople($leadDTO);

                // Создание бронирования банкета
////////               $reserve = $this->ikoTableReservationService->execute($leadDTO);

                $reserve = [
                    "correlationId" => "48fb4cd3-2ef6-4479-bea1-7c92721b988c",
                    "reserves" => [
                        [
                            "id" => "497f6eca-6276-4993-bfeb-53cbbbba6f08",
                            "externalNumber" => "string",
                            "organizationId" => "7bc05553-4b68-44e8-b7bc-37be63c6d9e9",
                            "timestamp" => 0,
                            "creationStatus" => "Success",
                            "errorInfo" => [
                                "code" => "Common",
                                "message" => "Какая то ошибка",
                                "description" => "string",
                                "additionalData" => null
                            ]
                        ]
                    ]
                ];


                if (empty($reserve['reserves'][0]['errorInfo'])) {
                    // Сохранение ID банкета
                    if ($reserveId = $reserve['reserves'][0]['id']) {
                        $this->amoLeadService->saveReserveId($leadDTO->getLeadId(), $reserveId);
                    }

                    // Комментарий в лид
                    $this->amoUpdateLeadService->execute(
                        leadId: $leadDTO->getLeadId(),
                        message: 'Статус успех, создан банкет: ' . $reserveId,
                        type: 'success',
                    );

                } else {
                    throw new Exception($reserve['reserves'][0]['errorInfo']['message']);
                }
                sleep(rand(1, 2));

            } catch (Exception $exception) {
                if (isset($leadDTO) && $leadDTO?->getLeadId()) {
                    // Комментарий в лид
                    $this->amoUpdateLeadService->execute(
                        leadId: $leadDTO->getLeadId(),
                        message: json_encode([
                            'code' => $exception->getCode(),
                            'message' => $exception->getMessage(),
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        type: 'error',
                    );

                    // Отключение синхронизации
                    $this->amoLeadService->disableSync($leadDTO->getLeadId());
                }

                LoggingService::save(
                    $exception->getMessage(),
                    "Error",
                    "webhook");
            }
        }

        // Ответ
        $this->response()->send(
            json_encode(['status' => 'success']),
            200,
            ['Content-Type: application/json'],
        );
    }
}