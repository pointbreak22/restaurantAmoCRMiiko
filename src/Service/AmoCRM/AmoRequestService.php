<?php

namespace App\Service\AmoCRM;

class AmoRequestService
{
    public function makeRequest($method, $url, $accessToken, $data = [])
    {
        http_response_code(200);
        $headers = [
            "Authorization: Bearer {$accessToken->getToken()}",
            "Content-Type: application/json",
        ];

        if (strtoupper($method) === "PATCH" && !str_contains($url, 'disable_webhooks=1')) {
            $url .= (!str_contains($url, '?') ? '?' : '&') . 'disable_webhooks=1';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//        if (curl_errno($ch)) {
//            return "cURL Error: " . curl_error($ch);
//        }

        //if ($httpCode >= 400) {

        //  return "Error: {$httpCode}, Response: " . $response;
        //  }
        http_response_code(200);
        curl_close($ch);
        http_response_code(200);
        $response = json_decode($response, true);
        return ['httpCode' => $httpCode, 'response' => $response];
        //  return json_decode($response, true);
    }
}