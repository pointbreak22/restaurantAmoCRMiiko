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
use Exception;
use Random\RandomException;

class WebhookController extends Controller
{
    private WebhookService $webhookService;
    private AmoAuthService $amoAuthService;

    private SetContactService $setContactService;
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

        try {

            if ($_POST['leads']['update'][0]['modified_user_id'] == 0) {
                // Игнорируем автоматический вебхук
                return;
            }

            if (!empty($data['leads']['update'])) {
                foreach ($data['leads']['update'] as $lead) {
                    foreach ($lead['custom_fields'] as $field) {
                        // Добавляем логику для конкретных полей
                        if ($field['id'] === 591981) {
                            $oldValue = $field['values'][0]['value'] ?? null;
                            $newValue = $field['values'][1]['value'] ?? null;

                            if ($oldValue !== null && $newValue !== null) {
                                // Если изменение касается ненужного поля, игнорируем
                                return;
                            }
                        }
                    }
                }
            }

            // Фильтруем только изменения сделок
            if (isset($webhookData['leads']['update'])) {
                $break = false;
                foreach ($webhookData['leads']['update'] as $lead) {
                    // Проверяем, было ли изменено поле с определенным ID
                    foreach ($lead['custom_fields'] as $field) {
                        if ($field['id'] === 591981) { // ID поля "ID резервации"
                            // Проверяем, если поле уже заполнено, игнорируем изменения
                            if (!empty($field['values'][0]['value'])) {
                                $break = true; // Прерываем выполнение, чтобы игнорировать вебхук
                            }
                        }
                    }
                }
                if ($break) {
                    return;
                }
            }
            if (isset($_POST['fields']['Поле удалено'])) {
                return;
            }
//
//            if ($_POST['changed_by'] === 'robot') {
//                return;
//            }
//
//
//            $lead = $_POST['leads']['update'][0];
//            if ($lead['status_id'] === $lead['old_status_id']) {
//                // Игнорируем, если статус не изменился
//                return;
//            }
//
//            $fields = $_POST['leads']['update'][0]['custom_fields'];
//
//            foreach ($fields as $field) {
//                if ($field['id'] === 591981) { // ID поля "Создать резерв"
//                    if (!empty($field['values'][0]['value'])) {
//                        // Игнорируем, если значение поля не "Да"
//                        return;
//                    }
//                }

        } catch (Exception $ex) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($ex, true));
        }
//        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($_POST['leads']['update'][0]['modified_user_id'], true));
//        //  return;

        $hookDataDTO = $this->webhookService->startProcessing();
//
        //  $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result2 ----------- " . print_r($hookDataDTO, true));
        //   return;

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
        //       $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Обычный вывод: " . print_r($accessToken, true));


        $this->amoNoteService = new AmoNoteService($accessToken);

        $this->setContactService = new SetContactService($accessToken);
        $result = $this->setContactService->setContactsByLead($hookDataDTO);

//        if (isset($result['httpCode']) && $result['httpCode'] >= 400) {
//                $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Обычный вывод" . print_r($hookDataDTO, true));
//                return;
//
//             }

        //получить поля в сделке


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


        if (isset($result['httpCode']) && $result['httpCode'] >= 400) {
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Error" . print_r($result, true));
            return;
        }


        if ($hookDataDTO->isCreatedReserve() && $hookDataDTO->getIdReserve() == '') {

            //   $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result " . print_r($hookDataDTO, true));

            $result = $this->ikoTableReservationService->execute($hookDataDTO);
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "result " . print_r($result, true));


            if ($result["httpCode"] == 200) {

                if (empty($result['response']['reserveInfo']['errorInfo'])) {
                    $idReserve = $result['response']['reserveInfo']['id'];
                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статут успех. Резерв создан на рассмотрение " . $idReserve);
                    if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($resultNode['response'], true));
                    }


                    $resultNode = $this->amoNoteService->editReserveInfo($hookDataDTO->getLeadId(), $idReserve);
                    if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($resultNode['response'], true));

                    }
                } else {
                    $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result['data']['reserveInfo']['errorInfo']['message'], true));
                    if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Статус ошибка " . print_r($resultNode['response'], true));
                    }
                }

            } else {
                $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), "Статус ошибка: " . print_r($result, true));
                if (isset($resultNode['httpCode']) && $resultNode['httpCode'] >= 400) {
                    $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Статус ошибка " . print_r($resultNode['response'], true));
                }
            }

            //  $resultNode = $this->amoNoteService->addNoteToLead($hookDataDTO->getLeadId(), json_encode($result));


        } else {
            $resultNode = "отключено создание резерва";
            $this->webhookService->logToFile(AMO_WEBHOOK_FILE, "Статус предупреждение: " . print_r($resultNode, true));


        }
        $this->webhookService->logToFile(AMO_WEBHOOK_FILE, print_r($_POST, true));


    }
}