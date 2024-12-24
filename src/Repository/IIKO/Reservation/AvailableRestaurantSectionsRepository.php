<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

/**
 * todo: Проверить наличие свободных банкетов
 */
class AvailableRestaurantSectionsRepository extends MainRepository
{
    private string $method = '/api/1/reserve/available_restaurant_sections';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($terminalGroupIds, $apiToken)
    {
        $params = [
            'terminalGroupIds' => $terminalGroupIds,
        ];

        return $this->request($this->method, $params, $apiToken);
    }
}