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

    /**
     * todo: Изменить HookDataDTO (переименовать, формировать его в сервисе или репозитории)
     * todo: Проверки условий заполненности полей вынести в отдельный сервис
     * todo: Создание бронирования и проверку ответа убрать из контроллера
     */
    public function handleWebhook(): void
    {
        $data = $_POST;
        try {
            $leadDTO = $this->amoLeadService->getLeadDTO($data);
            $this->amoCheckLeadService->checkDTO($leadDTO);
            $reserve = $this->ikoTableReservationService->execute($leadDTO);


            // Комментарий в лид
            if (empty($reserve['reserves'][0]['errorInfo'])) {
                $this->amoUpdateLeadService->execute(
                    leadId: $leadDTO->getLeadId(),
                    message: '',
                    type: 'success',
                );

                // Сохранение ID банкета
                if ($reserveId = $reserve['reserves'][0]['id']) {
                    $this->amoLeadService->saveReserveId($leadDTO->getLeadId(), $reserveId);
                }

            } else {
                throw new Exception(json_encode($reserve['reserves'][0]['errorInfo']["message"]));
            }

        } catch (Exception $exception) {
            if (isset($leadDTO) && $leadDTO?->getLeadId()) {
                // Комментарий в лид
                $this->amoUpdateLeadService->execute(
                    leadId: $leadDTO->getLeadId(),
                    message: json_encode([
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                    ], JSON_PRETTY_PRINT),
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