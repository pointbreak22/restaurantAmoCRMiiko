<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

class TerminalGroupRepository extends MainRepository
{
    private string $method = '/api/1/terminal_groups';

    public function __construct()
    {
        parent::__construct();
    }

    public function get(array $organizations, $apiToken): array
    {
        $params = [
            'organizationIds' => $organizations,
        ];

        return $this->request($this->method, $params, $apiToken);
    }
}