<?php

namespace App\Service;


use App\Repository\IIKO\Reservation\AvailableRestaurantSectionsRepository;
use App\Repository\IIKO\Reservation\OrganizationRepository;
use App\Repository\IIKO\Reservation\TableRepository;
use App\Repository\IIKO\Reservation\TerminalGroupRepository;
use Exception;


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

    private IikoTokenService $iikoTokenService;

    function __construct()
    {
        $this->organizationRepository = new OrganizationRepository();
        $this->terminalGroupRepository = new TerminalGroupRepository();
        $this->availableRestaurantSectionsRepository = new AvailableRestaurantSectionsRepository();
        $this->customerRepository = new CustomerRepository();
        $this->tableRepository = new TableRepository();
        $this->iikoTokenService = new IikoTokenService();
    }

    /**
     * Главная функция резервации стола
     * @throws Exception
     */
    public function execute(HookDataDTO $hookDataDTO)
    {

        $tokenResult = $this->iikoTokenService->getToken();

        if (isset($tokenResult['status']) && $tokenResult['status'] >= 400) {
            return ['httpCode' => $tokenResult['status'], 'response' => $tokenResult['data']];
        }

        if (!isset($tokenResult['token'])) {
            return $tokenResult;
        }


        // return ['httpCode'=>200, 'response' => $tokenResult['response']];

        //////!!!!!!!!
        // return [$name, $email, $phone, $dateVisit, $durationInMinutes, $banketName];
        $organizationsId = $this->getOrganisationsId();  //получает организацию


        //  dd($organizationsId);

        $terminalGroupId = $this->getTerminalGroupsId([$organizationsId]); // из организации получает термальную группу.
        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupId], $hookDataDTO->getNameReserve()); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки

        if (is_array($tables) && isset($tables['httpCode']) && $tables['httpCode'] >= 400) {
            return $tables;
        }

        $customerDTO = $this->getCustomer($organizationsId, $hookDataDTO->getContactPhone(), $hookDataDTO->getContactName(), $hookDataDTO->getContactEmail());
        if (is_array($customerDTO) && isset($customerDTO['httpCode']) && $customerDTO['httpCode'] >= 400) {
            return $customerDTO;
        }

        $tableResult = $this->setTable($organizationsId, $terminalGroupId, $customerDTO, $hookDataDTO->getContactPhone(), [$tables[0]], $hookDataDTO->getDataReserve(), $hookDataDTO->getTimeReserve(), $hookDataDTO->getCountPeople());
        return ['httpCode' => $tableResult['status'], 'response' => $tableResult['data']];


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

        $targetSection = array_filter($restaurantSections, function ($section) use ($banketName) {
            return $section['name'] === $banketName;
        });
        // Если нужен только первый элемент
        $targetSection = reset($targetSection);

        if (empty($targetSection)) {

            return ['httpCode' => 400, 'response' => "Отсутствуют столы в резерве:" . $banketName];
        }


        //  dd($targetSection);
        $result = array_map(function ($table) {
            return $table['id'];
        }, $targetSection['tables']);

        // dd($result);
        return $result;

    }

    private function getCustomer($organizationId, $phone, $name, $email): CustomerDTO|array|null
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


            $result = $this->customerRepository->set($customerDTO);
            if (isset($result['status']) && $result['status'] >= 400) {

                //  return  [$result['status'],$result['status']]];
                return ['httpCode' => $result['status'], 'response' => $result['data']];
            }
            $customerDTO = $this->customerRepository->get($organizationId, $phone);
            //  dd($customerDTO);
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