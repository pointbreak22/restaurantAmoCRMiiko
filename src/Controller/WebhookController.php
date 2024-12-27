<?php

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoCheckLeadService;
use App\Service\AmoCRM\AmoCommentService;
use App\Service\AmoCRM\AmoLeadService;
use App\Service\IikoTableReservationService;
use App\Service\LoggingService;
use Exception;

class WebhookController extends Controller
{
    private IikoTableReservationService $ikoTableReservationService;
    private AmoLeadService $amoLeadService;
    private AmoCheckLeadService $amoCheckLeadService;
    private AmoCommentService $amoUpdateLeadService;

    function __construct()
    {
        $this->ikoTableReservationService = new IikoTableReservationService();
        $this->amoLeadService = new AmoLeadService();
        $this->amoCheckLeadService = new AmoCheckLeadService();
        $this->amoUpdateLeadService = new AmoCommentService();
    }

    public function handleWebhook(): void
    {
        try {
            // Проверка статуса приложения
            if (APP_IS_WORK === 'true') {

                $data = $_POST;

                $leadDTO = $this->amoLeadService->getLeadDTO($data);
                $this->amoCheckLeadService->checkDTO($leadDTO);
                $reserve = $this->ikoTableReservationService->execute($leadDTO);  //нужно


                // Комментарий в лид
                if (empty($reserve['reserves'][0]['errorInfo'])) {
                    // Сохранение ID банкета
                    if ($reserveId = $reserve['reserves'][0]['id']) {
                        $this->amoLeadService->saveReserveId($leadDTO->getLeadId(), $reserveId);
                    }

                    $this->amoUpdateLeadService->execute(
                        leadId: $leadDTO->getLeadId(),
                        message: 'Статус успех, создан банкет: ' . $reserveId,
                        type: 'success',
                    );

                } else {
                    throw new Exception($reserve['reserves'][0]['errorInfo']['message']);
                }
            }
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