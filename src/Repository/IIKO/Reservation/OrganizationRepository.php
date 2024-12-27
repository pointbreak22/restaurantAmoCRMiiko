<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

/**
 * Подключение конфигов, получение организации
 *
 */
class OrganizationRepository extends MainRepository
{
    private string $method = '/api/1/organizations';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($apiToken, $skipErrorOutput = false): array
    {
        $params = [
            "returnAdditionalInfo" => true,
        ];

        return $this->request($this->method, $params, $apiToken, $skipErrorOutput);
    }
}