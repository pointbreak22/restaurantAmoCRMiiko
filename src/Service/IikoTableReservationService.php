<?php

namespace App\Service;


use App\DTO\CustomerDTO;
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
    public function execute(string $name = 'unknownName', string $email = "unknown@gmail.com", string $phone = "+72323232323", $dateVisit = '2024-12-20 14:15:22.123', int $customerCount = 1, int $durationInMinutes = 60, string $banketName = "Знахарь")
    {
        //////!!!!!!!!
        ///

        // return [$name, $email, $phone, $dateVisit, $durationInMinutes, $banketName];
        $organizationsId = $this->getOrganisationsId();  //получает организацию


        //  dd($organizationsId);

        $terminalGroupId = $this->getTerminalGroupsId([$organizationsId]); // из организации получает термальную группу.
        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupId], $banketName); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки


        $customerDTO = $this->getCustomer($organizationsId, $phone, $name, $email);


        return $customerDTO;
        $table = $this->setTable($organizationsId, $terminalGroupId, $customerDTO, $phone, [$tables[0]], $dateVisit, $durationInMinutes, $customerCount);
        return $table;


        //   dd($availableRestaurantSection);
//        $params = [
//            'organisationId' => $this->getOrganisationId(),
//        ];

//        $tableReservationId = $this->tableRepository->addReservation($params);
    }

    private function getOrganisationsId(): string
    {

        $response = $this->organizationRepository->get();
        // dd($response);
        // Извлечение массива id организаций
        $result = array_map(function ($organization) {
            return $organization['id'];
        }, $response['data']['organizations'])[0];

        return $result;
    }

    private function getTerminalGroupsId($organization): string
    {
        $response = $this->terminalGroupRepository->get($organization);
        //  dd($response);
        $result = array_map(function ($terminalGroup) {
            return $terminalGroup['id'];
        }, $response['data']['terminalGroups'][0]['items'])[0];
        //     dd($result);
        return $result;
    }

    private function getAvailableRestaurantSectionsId($terminalGroup, $banketName): array
    {
        $response = $this->availableRestaurantSectionsRepository->get($terminalGroup);


        $restaurantSections = $response['data']['restaurantSections'];

        $targetSection = array_filter($restaurantSections, function ($section) {
            return $section['name'] === 'Знахарь';
        });
        // Если нужен только первый элемент
        $targetSection = reset($targetSection);


        //  dd($targetSection);
        $result = array_map(function ($table) {
            return $table['id'];
        }, $targetSection['tables']);

        // dd($result);
        return $result;

    }
//
//    private function setCustomer($phone, $organizationId): array
//    {
//
//        return $this->customerRepository->set($phone, $organizationId);
//    }

    private function getCustomer($organizationId, $phone, $name, $email): ?CustomerDTO
    {
        $customerDTO = $this->customerRepository->get($organizationId, $phone);


        if ($customerDTO == null) {


            $customerDTO = new CustomerDTO(
                id: null,
                phone: $phone,
                cardTrack: "",
                cardNumber: "",
                name: $name,
                middleName: "",
                surName: "",
                birthday: '1996-03-02 14:15:22.123',
                email: $email,
                sex: 1,
                consentStatus: 0,
                shouldReceivePromoActionsInfo: null,
                userData: "test",
                organizationId: $organizationId
            );


            $this->customerRepository->set($customerDTO);
            $customerDTO = $this->customerRepository->get($organizationId, $phone);

            //   dd($customerDTO);

        }

        // If
        return $customerDTO;
    }

    private function setTable($organizationsId, $terminalGroupId, $customer, $phone, $tables, $dateVisit, $durationInMinutes, $customerCount): array
    {

        return $this->tableRepository->set($organizationsId, $terminalGroupId, $customer, $phone, $tables, $dateVisit, $durationInMinutes, $customerCount);
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