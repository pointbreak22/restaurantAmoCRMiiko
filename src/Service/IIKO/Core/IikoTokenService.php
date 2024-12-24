<?php

namespace App\Service\IIKO\Core;


use App\Repository\IIKO\Token\TokenRepository;
use Exception;

define('IIKO_TOKEN_FILE', APP_PATH . '/var/tmp/iiko_token_info.json');

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
     * @throws Exception
     */
    public function getToken()
    {
        $tokenData = $this->getFileToken();
        //  dd($tokenData);
        if (empty($tokenData['token'])) {
            $result = $this->getNewApiToken();

            if (isset($result['status']) && $result['status'] >= 401) {
                return $result;
            }

            return $result['token'];

        }
        // dd($tokenData);
        return $tokenData['token'];
    }

    /**
     * @throws Exception
     */
    public function getNewToken()
    {
        $result = ($this->getNewApiToken());

        if (isset($result['status']) && $result['status'] >= 401) {
            return $result;
        }
        return $result['token'];
    }


    /**
     * @throws Exception
     */
    public function getNewApiToken(): array
    {
        $result = $this->tokenRepository->get();

        if (isset($result['status']) && $result['status'] >= 401) {
            return $result;
        }


        $this->setFileToken($result['data']);
        return $result['data'];

    }

    /**
     * @throws Exception
     */
    public function getFileToken(): array
    {// Проверяем, существует ли файл
        if (!file_exists(IIKO_TOKEN_FILE)) {
            // Выбрасываем исключение или возвращаем ошибку, если файл не существует

            return $this->getNewApiToken();
        }
        // Если файл существует, читаем его содержимое
        $fileContent = file_get_contents(IIKO_TOKEN_FILE);
        // Преобразуем содержимое в массив
        $tokenData = unserialize(json_decode($fileContent, true));
        if (empty($tokenData)) {

            //dd(empty($tokenData));
            $tokenData = $this->getNewApiToken();
        }


        return $tokenData;
    }

    private function setFileToken($data): void
    {//   dd(APP_PATH . '/var/tmp/' . $fileName);

        // $filePath = APP_PATH . '/var/tmp/' . $fileName;

        // Проверяем, существует ли файл
        if (!file_exists(IIKO_TOKEN_FILE)) {
            // Если файл не существует, создаем его с нужными правами
            file_put_contents(IIKO_TOKEN_FILE, json_encode(serialize($data)));

            // Устанавливаем права на файл, чтобы он был редактируемым
            chmod(IIKO_TOKEN_FILE, 0777);  // Для чтения и записи владельцу и группе, только чтение остальным
        } else {
            // Если файл существует, обновляем его содержимое
            file_put_contents(IIKO_TOKEN_FILE, json_encode(serialize($data)));
        }

    }
}