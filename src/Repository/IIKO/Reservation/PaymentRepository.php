<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

class PaymentRepository extends MainRepository
{
    private string $method = '/api/1/payment_types';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($organizationsId, $apiToken): array
    {
        $params = [
            "organizationIds" => $organizationsId,
        ];

        return $this->request($this->method, $params, $apiToken);
    }


}