<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

/**
 * todo: Проверить наличие свободных банкетов
 */
class AvailableRestaurantSectionsRepository extends MainRepository //
{
    private string $method = '/api/1/reserve/available_restaurant_sections';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($terminalGroupIds)
    {
        $params = [
            'terminalGroupIds' => $terminalGroupIds,  //cec5c046-3821-4b67-b24d-3630d46b29f1 497f6eca-6276-4993-bfeb-53cbbbba6f08
            //    "returnSchema" => true,
            //  "revision" => 0,

        ];

        return $this->request($this->method, $params);
    }
}