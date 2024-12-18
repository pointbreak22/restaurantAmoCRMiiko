<?php

namespace App\DTO;

readonly class CustomerDTO
{
    function __construct(
        public ?string $id,//": "497f6eca-6276-4993-bfeb-53cbbbba6f08",
        public string  $phone,//": "string", //+
        public ?string $cardTrack,//": "string", //+
        public ?string $cardNumber,//": "string", /+
        public string  $name,//": "string", //+
        public ?string $middleName,//": "string", //+
        public ?string $surName,//": "string",  //+
        public ?string $birthday,//": "2019-08-24 14:15:22.123", //+
        public ?string $email,//": "string", //+
        public int     $sex,//": 0, //+
        public int     $consentStatus,//": 0, //+
        public ?bool   $shouldReceivePromoActionsInfo,//": true, //+
        //   public string    $referrerId,//": "string", //-
        public ?string $userData,//": "string", //+
        public string  $organizationId,//


    )
    {
    }
}