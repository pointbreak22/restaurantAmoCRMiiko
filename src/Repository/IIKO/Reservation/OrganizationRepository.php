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

    public function get()
    {
        $params = [
            //'organizationIds' => [],  //cec5c046-3821-4b67-b24d-3630d46b29f1 497f6eca-6276-4993-bfeb-53cbbbba6f08
            //"returnAdditionalInfo" => true,
            //   "includeDisabled" => true,
            //"returnExternalData" => ["string"]
        ];

        return $this->request($this->method, $params);
    }
}