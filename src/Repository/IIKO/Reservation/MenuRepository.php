<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

class MenuRepository extends MainRepository
{
    private string $method = '/api/1/nomenclature';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($organization, $apiToken)
    {
        $params = [
            'organizationId' => $organization,
        ];

        return $this->request($this->method, $params, $apiToken);
    }
}