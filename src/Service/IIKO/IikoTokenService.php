<?php

namespace App\Service\IIKO;

use RuntimeException;

class IikoTokenService
{

    private IikoConnectService $iikoConnectService;

    function __construct()
    {
        $this->iikoConnectService = new IikoConnectService();
    }

    public function getToken($isNew = false)
    {
        if ($isNew) {

            return $this->getNewApiToken()['token'];
        } else {
            $tokenData = $this->getFileToken("token.json");
            //  dd($tokenData);
            if (empty($tokenData['token'])) {
                return $this->getNewApiToken()['token'];
            }
            return $tokenData['token'];
        }

    }

    public function getCorrelationId()
    {
        $tokenData = $this->getFileToken("token.json");

        if (empty($tokenData['correlationId'])) {
            return $this->getNewApiToken()['correlationId'];
        }
        return $tokenData['correlationId'];
    }


    private function getKeyParameter(): array
    {
        return ['apiLogin' => IIKO_API_KEY];
    }


    public function getNewApiToken()
    {
        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
        $url = $iikoConfig['url'];
        $controller = $iikoConfig['getToken'];
        $postData = $this->getKeyParameter();
        $result = $this->iikoConnectService->ResponseDataApi($url, $controller, $postData);

        if (isset($result['data'])) {
            $this->setFileToken($result['data'], 'token.json');
            return $result['data'];
        } else {
            throw new RuntimeException('Ошибка при получение токена из ключа');
        }
    }

    private function getFileToken($fileName): array
    {
        $filePath = APP_PATH . '/var/tmp/' . $fileName;

        // Проверяем, существует ли файл
        if (!file_exists($filePath)) {
            // Выбрасываем исключение или возвращаем ошибку, если файл не существует

            return $this->getNewApiToken();
        }
        // Если файл существует, читаем его содержимое
        $fileContent = file_get_contents($filePath);
        // Преобразуем содержимое в массив
        $tokenData = unserialize(json_decode($fileContent, true));
        if (empty($tokenData)) {

            $tokenData = $this->getNewApiToken();
        }

        return $tokenData;
    }

    private function setFileToken($data, $fileName): void
    {
        //   dd(APP_PATH . '/var/tmp/' . $fileName);

        $filePath = APP_PATH . '/var/tmp/' . $fileName;

        // Проверяем, существует ли файл
        if (!file_exists($filePath)) {
            // Если файл не существует, создаем его с нужными правами
            file_put_contents($filePath, json_encode(serialize($data)));

            // Устанавливаем права на файл, чтобы он был редактируемым
            chmod($filePath, 0777);  // Для чтения и записи владельцу и группе, только чтение остальным
        } else {
            // Если файл существует, обновляем его содержимое
            file_put_contents($filePath, json_encode(serialize($data)));
        }

    }
}