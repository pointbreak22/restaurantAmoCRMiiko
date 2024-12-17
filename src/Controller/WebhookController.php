<?php

namespace App\Controller;


//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;

use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\WebHookService;

class WebhookController extends Controller
{
    private WebhookService $webhookService;

    function __construct()
    {
        $this->webhookService = new WebhookService();
    }

    public function handleWebhook()
    {
        // Логируем данные для отладки

        $result = $this->webhookService->startProcessing();
        //  echo json_encode(['status' => 'success']);
        // dd($result);
//        $data = json_decode($input, true);
//
//        $this->logToFile('webhook.log', 'Webhook received: ' . print_r($data, true));
//
//        // Проверка на наличие данных о лидах
//        if (isset($data['_embedded']['leads'])) {
//            foreach ($data['_embedded']['leads'] as $lead) {
//                $leadId = $lead['id'] ?? 'N/A';
//                $leadName = $lead['name'] ?? 'N/A';
//                $this->logToFile('webhook_leads.log', "Lead ID: $leadId, Name: $leadName");
//            }
//        } else {
//            $this->logToFile('webhook.log', 'No leads data found in the request.');
//        }
//
//        // Возвращаем ответ
//        header('Content-Type: application/json');
//        http_response_code(200);
//        echo json_encode(['status' => 'success']);

        //dd($data);

    }

//    private function logToFile(string $filename, string $message): void
//    {
//        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
//        file_put_contents(APP_PATH . "/var/logs/$filename", $logMessage, FILE_APPEND);
//    }
}