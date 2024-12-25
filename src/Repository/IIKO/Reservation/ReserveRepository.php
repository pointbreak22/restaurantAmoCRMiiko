<?php

namespace App\Repository\IIKO\Reservation;

use App\DTO\ReserveDTO;
use App\Repository\IIKO\MainRepository;

class ReserveRepository extends MainRepository
{
    private string $setMethod = '/api/1/reserve/create';
    private string $getMethod = '/api/1/reserve/status_by_id';

    private CustomerRepository $customerRepository;

    public function __construct()
    {
        $this->customerRepository = new CustomerRepository();
        parent::__construct();
    }

    public function get(string $organizationId, array $reserveIds, string $apiToken)
    {
        $params = [
            'organizationId' => $organizationId,
            'reserveIds' => $reserveIds,
        ];
        return $this->request($this->getMethod, $params, $apiToken);

    }

    public function set(ReserveDTO $reserve, string $apiToken)
    {
        $phone = $reserve->getPhone();
        $params = [
            "organizationId" => $reserve->getOrganizationId(),  //организация
            "terminalGroupId" => $reserve->getTerminalGroupId(), //группа
            "order" => [
                //       "menuId" => null,
                "items" => [
                    [
                        "productId" => $reserve->getProductId(),
                        "price" => 0,
                        "type" => 'Product',
                        "amount" => 1
                    ],
                ],
                'payments' => [[
                    'paymentTypeKind' => "Card",
                    'sum' => $reserve->getSumReserve(),
                    'paymentTypeId' => $reserve->getPaymentId(),
                ],

                ]
            ],
            "customer" => $this->customerRepository->toArray($reserve->getCustomer()),
            "phone" => str_starts_with($phone, "+") ? $phone : "+" . $phone,
            "comment" => "test",
            "durationInMinutes" => $reserve->getDurationInMinutes(),  //продолжительность в минутах
            "shouldRemind" => false,  //должен напоминать
            "tableIds" => $reserve->getTables(),
            "estimatedStartTime" => $reserve->getDateVisit(),//старт банкета,
            "guests" => ["count" => $reserve->getCustomerCount()], //информация о гостей
        ];
        return $this->request($this->setMethod, $params, $apiToken);
    }
}