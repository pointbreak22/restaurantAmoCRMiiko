<?php

namespace App\Service\AmoCRM;

class AmoRequestService
{
    public function makeRequest($method, $url, $data = [])
    {

        $headers = [
            //  "Authorization: Bearer {$accessToken->getToken()}",
            "Authorization: Bearer " . AMO_TOKEN,
            "Content-Type: application/json",
        ];

        //   dd($headers);
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

        curl_close($ch);

        $response = json_decode($response, true);
        return ['status' => $httpCode, 'data' => $response];
        //  return json_decode($response, true);
    }
}