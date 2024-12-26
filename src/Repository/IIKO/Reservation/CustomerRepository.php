<?php

namespace App\Repository\IIKO\Reservation;

use App\DTO\CustomerDTO;
use App\Repository\IIKO\MainRepository;

/**
 * Получения клиента
 * todo: методы get/set
 */
class CustomerRepository extends MainRepository
{
    private string $setMethod = '/api/1/loyalty/iiko/customer/create_or_update';

    private string $getMethod = '/api/1/loyalty/iiko/customer/info';


    public function __construct()
    {
        parent::__construct();
    }

    public function get(string $organizationId, string $value, $apiToken = '', string $type = 'phone'): array|null|CustomerDTO
    {
        $params = [
            $type => $value,
            "type" => $type,
            "organizationId" => $organizationId
        ];


        $result = $this->request($this->getMethod, $params, $apiToken);
        return $this->response(result: $result, organizationId: $organizationId);
    }

    public function set(CustomerDTO $customerDTO, $apiToken)
    {

        $params = $this->toArray(customerDTO: $customerDTO);
        $result = $this->request($this->setMethod, $params, $apiToken);


        return $result;
    }

    private function response(array $result, string $organizationId): CustomerDTO|array|null
    {
        //  return $result;
        if ($result['status'] >= 400) {
            return null;
        } else {
            return new CustomerDTO(
                id: $result['data']['id'],
                phone: $result['data']['phone'],
                cardTrack: isset($result['data']['cards'][0]) ? $result['data']['cards'][0]['track'] : "",
                cardNumber: isset($result['data']['cards'][0]) ? $result['data']['cards'][0]['number'] : "",
                name: $result['data']['name'],
                middleName: $result['data']['middleName'],
                surName: $result['data']['surname'],
                birthday: $result['data']['birthday'] ?? date("Y-m-d H:i:s.v"),
                email: $result['data']['email'],
                sex: $result['data']['sex'],
                consentStatus: $result['data']['consentStatus'],
                shouldReceivePromoActionsInfo: $result['data']['shouldReceivePromoActionsInfo'],
                userData: $result['data']['userData'],
                organizationId: $organizationId
            );
        }
    }

    public function toArray(CustomerDTO $customerDTO): array
    {

        $data = [
            'id' => $customerDTO->id,
            'phone' => $customerDTO->phone,
            'cardTrack' => $customerDTO->cardTrack,
            'cardNumber' => $customerDTO->cardNumber,
            'name' => $customerDTO->name,
            'middleName' => $customerDTO->middleName,
            'surname' => $customerDTO->surName,
            'birthday' => $customerDTO->birthday,
            'email' => $customerDTO->email,
            'sex' => $customerDTO->sex,
            'consentStatus' => $customerDTO->consentStatus,
            'shouldReceivePromoActionsInfo' => $customerDTO->shouldReceivePromoActionsInfo,
            'userData' => $customerDTO->userData,
            'organizationId' => $customerDTO->organizationId
        ];
        // Удаляем ключи с пустыми или null значениями
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });

    }
}