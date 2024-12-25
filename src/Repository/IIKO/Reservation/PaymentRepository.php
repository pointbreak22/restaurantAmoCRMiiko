<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

class PaymentRepository extends MainRepository //available_restaurant_sections
{
    private string $method = '/api/1/payment_types';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($organizationsId, $apiToken)
    {
        $params = [
            "organizationIds" => $organizationsId,
            //   "includeDisabled" => true,
        ];

        return $this->request($this->method, $params, $apiToken);
    }


}