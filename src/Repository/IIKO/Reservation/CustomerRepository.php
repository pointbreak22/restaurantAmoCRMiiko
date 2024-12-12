<?php

namespace App\Repository\IIKO\Reservation;

use App\Repository\IIKO\MainRepository;

/**
 * Получения клиента
 * todo: методы get/set
 */
class CustomerRepository extends MainRepository //available_restaurant_sections
{
    private string $setMethod = '/api/1/loyalty/iiko/customer/create_or_update';

    private string $getMethod = '/api/1/loyalty/iiko/customer/info';


    public function __construct()
    {
        parent::__construct();
    }

    public function get($number)
    {
        $params = [
            "phone" => $number,
            "type" => "phone",
            "organizationId" => "7bc05553-4b68-44e8-b7bc-37be63c6d9e9"
        ];

        return $this->request($this->getMethod, $params);
    }

    public function set($number, $organizationId)
    {

        $params = [
            //     "id" => "497f6eca-6276-4993-bfeb-53cbbbba6f08",
            "phone" => $number,
            //     "cardTrack" => "string",
            //     "cardNumber" => "string",
            "name" => "Test",
            //     "middleName" => "string",
            //     "surName" => "string",
            //     "birthday" => "2019-08-24 14:15:22.123",
            //      "email" => "string",
            "sex" => 1,
            "consentStatus" => 0,
            //     "shouldReceivePromoActionsInfo" => true,
            //       "referrerId" => "string",
            //        "userData" => "string",
            "organizationId" => $organizationId
        ];

        return $this->request($this->setMethod, $params);

    }
}