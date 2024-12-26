<?php

namespace App\Service\IIKO\Core;

use Exception;

class IikoHttpClient
{
    private array $headers = ['Content-Type: application/json', 'Accept: application/json'];

    /**
     * @throws Exception
     */
    public function execute(string $apiUrl, string $apiMethod, $apiToken = '', array $params = []): array
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


        if ($httpCode != 200) {
            throw new Exception(message: $response, code: $httpCode);
        } else if ($curlErrors = curl_error($ch)) {
            throw new Exception(message: $curlErrors, code: $httpCode);
        }

        curl_close($ch);

        $arrayList = json_decode($response, true);

        return ['status' => $httpCode, 'data' => $arrayList];


    }
}