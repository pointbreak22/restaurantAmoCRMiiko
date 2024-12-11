<?php

namespace App\Service\IIKO;

use RuntimeException;

class IikoConnectService
{


    public function ResponseDataApi(string $url, string $controller = '', array $postData = [], $token = null): array
    {

        // dd(JWT_TOKEN);
        $request = $url . $controller;


        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);


        // Set the HTTP headers, including the Authorization header with the API key
        $headers = [
            // 'Authorization: Bearer ' . JWT_TOKEN,
            'Content-Type: application/json',
            'Accept: application/json',
            //'Timeout: 15',
        ];

        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }


        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        // Получить статус HTTP
        // Execute the cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            curl_close($ch);
            throw new RuntimeException('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        $arrayList = json_decode($response, true);
//        if ($arrayList === null) {
//            throw new RuntimeException('Failed to decode JSON response');
//        }
        return ['status' => $httpCode, 'data' => $arrayList];
    }
}