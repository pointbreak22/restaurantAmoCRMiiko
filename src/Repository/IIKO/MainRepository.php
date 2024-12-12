<?php

namespace App\Repository\IIKO;

use App\Service\IIKO\Core\IikoApiService;

class MainRepository
{
    private $url = 'https://api-ru.iiko.services';

    private IikoApiService $apiService;

    public function __construct()
    {
        $this->apiService = new IikoApiService();
    }

    protected function request(string $method, mixed $params = []): mixed
    {

        return $this->apiService->execute($this->url, $method, $params);
    }
}