<?php

namespace App\Service\AmoCRM;

use Exception;

class AmoRequestService
{
    public function makeRequest($method, $url, $data = []): array
    {
        $headers = [
            "Authorization: Bearer " . AMO_TOKEN,
            "Content-Type: application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseData = json_decode($response, true);

        if ($httpCode != 200) {
            throw new Exception(message: $response, code: $httpCode);
        } else if ($curlErrors = curl_error($ch)) {
            throw new Exception(message: $curlErrors, code: $httpCode);
        }

        curl_close($ch);

        return $responseData;
    }
}