<?php

namespace App\Service\IIKO\Core;


use Exception;

class IikoApiService
{


    private IikoHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = new IikoHttpClient();
    }

    /**
     * @throws Exception
     */
    public function execute(string $apiUrl, string $apiMethod, array $params = []): array
    {


        // Prepare request
        $url = $apiUrl . $apiMethod;

        //    return $url;
        $headers = [];
        if (!isset($params['apiLogin'])) {
            $headers = ['Authorization: Bearer ' . $this->getToken()];
        }

        $response = $this->httpClient->execute($url, $headers, $params);

//        if (isset($response['status'])) {
//            if ($response['status'] === 401) {
//                $headers = ['Authorization: Bearer ' . $this->getNewToken()];
//                $response = $this->httpClient->execute($url, $headers, $params);
//
//            }
//        }
        return $response;

    }

    /**
     * @throws Exception
     */
    private function getToken(): string
    {
        return (new IikoTokenService())->getToken();
    }

//    /**
//     * @throws Exception
//     */
//    private function getNewToken(): string
//    {
//
//        return (new IikoTokenService())->getNewToken();
//    }
}