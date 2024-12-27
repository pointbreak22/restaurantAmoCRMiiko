<?php

namespace App\Service\IIKO\Core;

use Exception;

class IikoHttpClient
{
    private array $headers = ['Content-Type: application/json', 'Accept: application/json'];

    /**
     * @throws Exception
     */
    public function execute(
        string $apiUrl,
        string $apiMethod,
        string $apiToken = '',
        array  $params = [],
        bool   $skipErrorOutput = false): array
    {
        $url = $apiUrl . $apiMethod;
        $headers = [];
        if (!isset($params['apiLogin'])) {
            $headers = ['Authorization: Bearer ' . $apiToken];
        }
        // Prepare headers
        $headers = array_merge($this->headers, $headers);
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        // Execute the cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseData = json_decode($response, true);


        if ($httpCode != 401 && !empty($response) && !$skipErrorOutput) {
            if ($httpCode != 200) {
                throw new Exception(message: $response, code: $httpCode);
            } else if ($curlErrors = curl_error($ch)) {
                throw new Exception(message: $curlErrors, code: $httpCode);
            }
        } elseif ($skipErrorOutput) {
            $responseData = [];
        }

        if ($responseData === null) {
            throw  new Exception("Отсутствуют данные IIKO с параметрами:" . print_r([$apiMethod, $apiToken, $params, $skipErrorOutput, $httpCode, $responseData], true));
        }


        curl_close($ch);
        return $responseData;
    }
}