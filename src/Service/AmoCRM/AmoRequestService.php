<?php

namespace App\Service\AmoCRM;

class AmoRequestService
{
    private function makeRequest($method, $url, $data = [])
    {
        $headers = [
            "Authorization: Bearer {$this->accessToken}",
            "Content-Type: application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            return "Error: {$httpCode}, Response: " . $response;
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}