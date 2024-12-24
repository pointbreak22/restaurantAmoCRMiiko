<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

/**
 * Подключение конфигов, получение организации
 *
 */
class OrganizationRepository extends MainRepository //available_restaurant_sections
{
    private string $method = '/api/1/organizations';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($apiToken)
    {
        $params = [
            "returnAdditionalInfo" => true,
            //   "includeDisabled" => true,
        ];

        return $this->request($this->method, $params, $apiToken);
    }
}