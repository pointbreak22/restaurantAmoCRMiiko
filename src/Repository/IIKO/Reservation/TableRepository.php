<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

class TableRepository extends MainRepository
{
    private string $setMethod = '/api/1/reserve/create';


    public function __construct()
    {
        parent::__construct();
    }

    public function set($organizationId, $customer, $number, $tables)
    {
        $params = [
            "organizationId" => $organizationId,
            //   "terminalGroupId": "4fab19a5-203c-4bf5-94eb-f572aa8b117b",
            //"id": "497f6eca-6276-4993-bfeb-53cbbbba6f08",
            //  "externalNumber": "string",
            "customer" => $customer,
            "phone" => $number,
            //"guestsCount": 0,
            "comment" => "Данная заявка это фейк, тест Api",
            "durationInMinutes" => 60,
            "shouldRemind" => false,
            "tableIds" => $tables,
            "estimatedStartTime" => "2024-12-24 14:15:22.123",//date('Y-m-d H:i:s', strtotime('+1 week')),
            //  "transportToFrontTimeout": 0,
            "guests" => ["count" => 1],
            // "eventType": "string",
            // "createReserveSettings": {         "transportToFrontTimeout": 0,        //    "checkStopList": false
        ];

        return $this->request($this->setMethod, $params);
    }
}