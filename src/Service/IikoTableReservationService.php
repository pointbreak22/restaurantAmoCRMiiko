<?php

namespace App\Service;


use App\Repository\IIKO\Reservation\AvailableRestaurantSectionsRepository;
use App\Repository\IIKO\Reservation\CustomerRepository;
use App\Repository\IIKO\Reservation\OrganizationRepository;
use App\Repository\IIKO\Reservation\TableRepository;
use App\Repository\IIKO\Reservation\TerminalGroupRepository;

/**
 * step: инициализация подключение (получения токена)
 * step: получение организации
 * step: получение столов
 * step: получение клиента
 * step: создание резервации
 */
class IikoTableReservationService
{
    private OrganizationRepository $organizationRepository;
    private TerminalGroupRepository $terminalGroupRepository;
    private AvailableRestaurantSectionsRepository $availableRestaurantSectionsRepository;
    private CustomerRepository $customerRepository;
    private TableRepository $tableRepository;

    function __construct()
    {
        $this->organizationRepository = new OrganizationRepository();
        $this->terminalGroupRepository = new TerminalGroupRepository();
        $this->availableRestaurantSectionsRepository = new AvailableRestaurantSectionsRepository();


        $this->customerRepository = new CustomerRepository();
        $this->tableRepository = new TableRepository();


    }

    /**
     * Главная функция резервации стола
     */
    public function execute()
    {
        //////!!!!!!!!
        ///
        $number = "+7776655444326";
        $organizationsId = $this->getOrganisationsId();  //получает организацию
        //  dd($organizationsId);
        $terminalGroupId = $this->getTerminalGroupsId([$organizationsId]); // из организации получает термальную группу.
        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupId]); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки

        //dd($organizationsId);
        $customer = $this->setCustomer($number, $organizationsId)['data'];


        // $customer = $this->getCustomer($number);
        //  dd($customer);
        $table = $this->setTable($organizationsId, $customer, $number, $tables);
        dd($table);


        //   dd($availableRestaurantSection);
//        $params = [
//            'organisationId' => $this->getOrganisationId(),
//        ];

//        $tableReservationId = $this->tableRepository->addReservation($params);
    }

    private function getOrganisationsId(): string
    {

        $response = $this->organizationRepository->get();
        // Извлечение массива id организаций
        return array_map(function ($organization) {
            return $organization['id'];
        }, $response['data']['organizations'])[0];
    }

    private function getTerminalGroupsId($organization): string
    {
        $response = $this->terminalGroupRepository->get($organization);
        //  dd($response);
        return array_map(function ($terminalGroup) {
            return $terminalGroup['id'];
        }, $response['data']['terminalGroups'][0]['items'])[0];

    }

    private function getAvailableRestaurantSectionsId($terminalGroup): array
    {
        $response = $this->availableRestaurantSectionsRepository->get($terminalGroup);

        //dd($response['data']['restaurantSections'][0]['tables'][0]['id']);
        $result = array_map(function ($table) {
            return $table['id'];
        }, $response['data']['restaurantSections'][0]['tables']);

        //   dd($result);
        return $result;

    }

    private function setCustomer($number, $organizationId): array
    {

        return $this->customerRepository->set($number, $organizationId);
    }

    private function getCustomer($number): array
    {


        return $this->customerRepository->get($number);
    }

    private function setTable($organizationsId, $customer, $number, $tables): array
    {
        return $this->tableRepository->set($organizationsId, $customer, $number, $tables);
    }


//    public function getIikoAvailableRestaurant(): array
//    {
//        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
//        $url = $iikoConfig['url'];
//        $controller = $iikoConfig['getAvailableRestaurantSections'];
//        $postData = $this->getAvailableRestaurantSectionsParameters();
//        return $this->setResponseDataApi($url, $controller, $postData);
//    }
//
//    private function getAvailableRestaurantSectionsParameters(): array
//    {
//        return ['terminalGroupIds' => ["443707ed-5429-e7bd-0187-7433c8ff0064"],
//            "returnSchema" => true,
//            "revision" => 0];
//    }
//
//    public function getIikoRestaurantSectionsWorkload(): array
//    {
//        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
//        $url = $iikoConfig['url'];
//        $controller = $iikoConfig['getRestaurantSectionsWorkload'];
//        $postData = $this->getRestaurantSectionsWorkloadParameters();
//
//        return $this->setResponseDataApi($url, $controller, $postData);
//    }
//
//    private function getRestaurantSectionsWorkloadParameters(): array
//    {
//
//        return ['restaurantSectionIds' => ["497f6eca-6276-4993-bfeb-53cbbbba6f08"],
//            "dateFrom" => date('Y-m-d 00:00:00'),
//            "dateTo" => date('Y-m-d 23:59:59')
//        ];
//    }
//
//    public function getIikoTerminalGroups(): array
//    {
//        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
//        $url = $iikoConfig['url'];
//        $controller = $iikoConfig['getTerminalGroups'];
//        $postData = $this->getTerminalGroupsParameters();
//
//        return $this->setResponseDataApi($url, $controller, $postData);
//    }
//
//    private function getTerminalGroupsParameters(): array
//    {
//
//        return ['restaurantSectionIds' => ["497f6eca-6276-4993-bfeb-53cbbbba6f08"],
//            "dateFrom" => date('Y-m-d 00:00:00'),
//            "dateTo" => date('Y-m-d 23:59:59')
//        ];
//    }
//
//
//    public function getIikoOrganization(): array
//    {
//        return [];
//
//        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
//        $url = $iikoConfig['url'];
//        $controller = $iikoConfig['getOrganizations'];
//        $postData = $this->getOrganizationParameters();
//        return $this->setResponseDataApi($url, $controller, $postData);
//    }
//
//    private function getOrganizationParameters(): array
//    {
//        $correlationId = $this->tokenService->getCorrelationId();
//        //    dd($correlationId);
//        return ['organizationIds' => ["497f6eca-6276-4993-bfeb-53cbbbba6f08"],
//            "returnAdditionalInfo" => true,
//            "includeDisabled" => true,
//            "returnExternalData" => ["string"]];
//    }
//
//    private function setResponseDataApi($url, $controller, $postData): array
//    {
//        $token = $this->tokenService->getToken();
//        $result = $this->iikoConnectService->ResponseDataApi($url, $controller, $postData, $token);
//        if (!isset($result['status'])) {
//            throw new RuntimeException('Данные отсутствуют.');
//        }
//        if ($result['status'] >= 400 && $result['status'] < 500) {
//            $token = $this->tokenService->getToken(true);
//            $result = $this->iikoConnectService->ResponseDataApi($url, $controller, $postData, $token);
//        } elseif ($result['status'] >= 500) {
//            // Ошибки сервера
//            throw new RuntimeException("Server Error: HTTP Code " . $result['status']);
//        }
//        return $result;
//    }


}