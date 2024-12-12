<?php

namespace App\Service\IIKO\Core;

use App\Repository\IIKO\Reservation\TestRepository;
use App\Repository\IIKO\Token\TokenRepository;

/**
 * todo: Проверить наличие сохраненного токена
 * todo: Проверить его действительность
 * todo: Получить и записать новый ключ
 */
class IikoTokenService
{
    private TokenRepository $tokenRepository;


    function __construct()
    {
        $this->tokenRepository = new TokenRepository();


    }

    /**
     * @throws \Exception
     */
    public function getToken()
    {
        $tokenData = $this->getFileToken("token.json");
        //  dd($tokenData);
        if (empty($tokenData['token'])) {
            return $this->getNewApiToken()['token'];//['token']
        }
        // dd($tokenData);
        return $tokenData['token'];
    }

    /**
     * @throws \Exception
     */
    public function getNewToken()
    {
        return $this->getNewApiToken()['token'];
    }

//    public function getCorrelationId()
//    {
//        $tokenData = $this->getFileToken("token.json");
//
//        if (empty($tokenData['correlationId'])) {
//            return $this->getNewApiToken();//['correlationId']
//        }
//        return $tokenData['correlationId'];
//    }


    /**
     * @throws \Exception
     */
    public function getNewApiToken(): array
    {
        $result = $this->tokenRepository->get();

        if (!isset($result['data'])) {
            throw new \Exception('Ошибка при получение токена из ключа');
        }
        $this->setFileToken($result['data'], 'token.json');
        return $result['data'];

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

            //dd(empty($tokenData));
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