<?php

namespace App\Repository\IIKO\Reservation;

use App\DTO\CustomerDTO;
use App\Repository\IIKO\MainRepository;

class TableRepository extends MainRepository
{
    private string $setMethod = '/api/1/reserve/create';


    private CustomerRepository $customerRepository;

    public function __construct()
    {
        $this->customerRepository = new CustomerRepository();
        parent::__construct();
    }

    public function set($organizationId, $terminalGroupId, CustomerDTO $customer, $number, $tables, $dateVisit, $durationInMinutes, $customerCount)
    {
        $params = [
            "organizationId" => $organizationId,  //организация
            "terminalGroupId" => $terminalGroupId, //группа
            //"id": "497f6eca-6276-4993-bfeb-53cbbbba6f08",
            //  "externalNumber": "string", //номер банкета
            "customer" => $this->customerRepository->toArray($customer),
            "phone" => $number,
            //"guestsCount": 0,
            "comment" => "Данная заявка это фейк, тест Api",
            "durationInMinutes" => $durationInMinutes,  //продолжительность в минутах
            "shouldRemind" => false,  //должен напоминать
            "tableIds" => $tables,
            "estimatedStartTime" => $dateVisit,//старт банкета,
            //  "transportToFrontTimeout": 0,
            "guests" => ["count" => $customerCount], //информация о гостей
            // "eventType": "string",
            // "createReserveSettings": {         "transportToFrontTimeout": 0,        //    "checkStopList": false
        ];


        //  dd($params);
        return $this->request($this->setMethod, $params);
    }
}