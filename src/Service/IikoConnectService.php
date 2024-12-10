<?php

namespace App\Service;

use RuntimeException;

class IikoConnectService
{

    public function testResponse(): array
    {
        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
        $url ='https://api-ru.iiko.services/api/1/loyalty/iiko/customer/card/add';
   //     $url = $iikoConfig['url'];
     //   $controller = $iikoConfig['menu'];
        $postParams = [
            "customerId" => "87d8e330-2878-4742-a86f-dbbb3bf522ac",
            "cardTrack"=>"string",
            "cardNumber"=> "string",
            "organizationId" => "7bc05553-4b68-44e8-b7bc-37be63c6d9e9",
        ];

        return $this->ResponseDataApi($url, "","",$postParams);
    }


    public function ResponseDataApi(string $url, string $controller, string $parameter='', array $postData=[]): array
    {

      //  $request = $url . $controller . $parameter;
      //  $request = "https://api-ru.iiko.services/api/2/menu";
       // dd($request);
        $jsonFile ='test.json';

     //   dd( "Текущая директория: " . getcwd());

        $request = "https://api-ru.iiko.services/apiiii/1/loyalty/iiko/customer/card/add";
        $jsonContent = file_get_contents($jsonFile);

       // dd($jsonContent);
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

        // Set the HTTP headers, including the Authorization header with the API key
        $headers = [
            'Authorization: Bearer ' . IIKO_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

     //   dd(IIKO_API_KEY);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


    //    if (!empty($postData)) {

            // Indicate this is a POST request
            curl_setopt($ch, CURLOPT_POST, true);

            // Attach the POST data as JSON

            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonContent);
       //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
       // }

        // Execute the cURL request
        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            throw new RuntimeException('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
        dd($response);
        $arrayList = json_decode($response, true);
        if ($arrayList === null) {
            throw new RuntimeException('Failed to decode JSON response');
        }
        return $arrayList;
    }
}