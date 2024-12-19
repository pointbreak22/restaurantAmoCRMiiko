<?php

namespace App\Service\AmoCRM;

class AmoRequestService
{
    public function makeRequest($method, $url, $accessToken, $data = [])
    {
        $headers = [
            "Authorization: Bearer {$accessToken->getToken()}",
            "Content-Type: application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);


        if (!empty($data)) {

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//        if (curl_errno($ch)) {
//            return "cURL Error: " . curl_error($ch);
//        }

        if ($httpCode >= 400) {

            //  return "Error: {$httpCode}, Response: " . $response;
        }

        curl_close($ch);
        $response = json_decode($response, true);
        return ['httpCode' => $httpCode, 'response' => $response];
        //  return json_decode($response, true);
    }
}