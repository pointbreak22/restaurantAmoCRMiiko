<?php

namespace App\Repository\IIKO;


use App\Service\IIKO\Core\IikoHttpClient;

class MainRepository
{
    private $url = 'https://api-ru.iiko.services';

    private IikoHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = new IikoHttpClient();
    }

    protected function request(string $method, mixed $params = [], $apiToken = ''): array
    {

        return $this->httpClient->execute($this->url, $method, $apiToken, $params);
    }
}