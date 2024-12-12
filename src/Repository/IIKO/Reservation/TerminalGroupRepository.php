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

    public function get(array $organizations)
    {
        $params = [
            'organizationIds' => $organizations,  //cec5c046-3821-4b67-b24d-3630d46b29f1 497f6eca-6276-4993-bfeb-53cbbbba6f08
            //"returnAdditionalInfo" => true,
            //   "includeDisabled" => true,
            //"returnExternalData" => ["string"]
        ];

        return $this->request($this->method, $params);
    }
}