<?php

namespace App\Service;


use App\DTO\CustomerDTO;
use App\DTO\HookDataDTO;
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
    public function execute(HookDataDTO $hookDataDTO)
    {
        //////!!!!!!!!
        // return [$name, $email, $phone, $dateVisit, $durationInMinutes, $banketName];
        $organizationsId = $this->getOrganisationsId();  //получает организацию


        //  dd($organizationsId);

        $terminalGroupId = $this->getTerminalGroupsId([$organizationsId]); // из организации получает термальную группу.
        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupId], $hookDataDTO->getNameReserve()); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки

        $customerDTO = $this->getCustomer($organizationsId, $hookDataDTO->getContactPhone(), $hookDataDTO->getContactName(), $hookDataDTO->getContactEmail());

        //return $customerDTO;
        $table = $this->setTable($organizationsId, $terminalGroupId, $customerDTO, $hookDataDTO->getContactPhone(), [$tables[0]], $hookDataDTO->getDataReserve(), $hookDataDTO->getTimeReserve(), $hookDataDTO->getCountPeople());
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

}