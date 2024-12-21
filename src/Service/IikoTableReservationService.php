<?php

namespace App\Service;


use App\DTO\CustomerDTO;
use App\DTO\HookDataDTO;
use App\Repository\IIKO\Reservation\AvailableRestaurantSectionsRepository;
use App\Repository\IIKO\Reservation\CustomerRepository;
use App\Repository\IIKO\Reservation\OrganizationRepository;
use App\Repository\IIKO\Reservation\TableRepository;
use App\Repository\IIKO\Reservation\TerminalGroupRepository;
use App\Service\IIKO\Core\IikoTokenService;
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
            return $tokenResult;
        }
        // return ['status' => 200, 'data' => $tokenResult];
        $organizationsId = $this->getOrganisationsId();  //получает организацию
        if (isset($organizationsId['status']) && $organizationsId['status'] == 401 && empty($tokenResult['data'])) {
            $tokenResult = $this->iikoTokenService->getNewToken();

            if (isset($tokenResult['status']) && $tokenResult['status'] >= 400) {
                return $tokenResult;
            }
            $organizationsId = $this->getOrganisationsId();
        }
        if (isset($organizationsId['status']) && $organizationsId['status'] >= 400) {
            return $organizationsId;
        }
        if (count($organizationsId) == 0) {
            return ['status' => 400, 'data' => "Отсутствуют организации"];
        }
        $terminalGroupsId = $this->getTerminalGroupsId([$organizationsId[0]]); // из организации получает термальную группу.
        if (isset($terminalGroupsId['status']) && $terminalGroupsId['status'] >= 400) {
            return $terminalGroupsId;
        }
        if (count($terminalGroupsId) == 0) {
            return ['status' => 400, 'data' => "Отсутствуют группы"];
        }
        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupsId[0]], $hookDataDTO->getNameReserve()); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки
        if (isset($tables['status']) && $tables['status'] >= 400) {
            return $tables;
        }
        $customerDTO = $this->getCustomer($organizationsId[0], $hookDataDTO->getContactPhone(), $hookDataDTO->getContactName(), $hookDataDTO->getContactEmail());
        if (is_array($customerDTO) && isset($customerDTO['status']) && $customerDTO['status'] >= 400) {
            return $customerDTO;
        }
        //  return ['status' => 200, 'data' => $customerDTO];
        $tableResult = $this->setTable($organizationsId[0], $terminalGroupsId[0], $customerDTO, $hookDataDTO->getContactPhone(), [$tables[0]], $hookDataDTO->getDataReserve(), $hookDataDTO->getTimeReserve(), $hookDataDTO->getCountPeople());
        return $tableResult;
    }

    private function getOrganisationsId(): array
    {
        $response = $this->organizationRepository->get();
        if (isset($response['status']) && $response['status'] >= 400) {
            return $response;
        }

        $result = array_map(function ($organization) {
            return $organization['id'];
        }, $response['data']['organizations']);

        return $result;
    }

    private function getTerminalGroupsId($organization): array
    {
        $response = $this->terminalGroupRepository->get($organization);
        if (isset($response['status']) && $response['status'] >= 400) {
            return $response;
        }
        //  dd($response);
        $result = array_map(function ($terminalGroup) {
            return $terminalGroup['id'];
        }, $response['data']['terminalGroups'][0]['items']);

        return $result;
    }

    private function getAvailableRestaurantSectionsId($terminalGroup, $bankedName): array
    {
        $response = $this->availableRestaurantSectionsRepository->get($terminalGroup);
        if (isset($response['status']) && $response['status'] >= 400) {
            return $response;
        }

        $restaurantSections = $response['data']['restaurantSections'];
        $targetSection = array_filter($restaurantSections, function ($section) use ($bankedName) {
            return $section['name'] === $bankedName;
        });
        // Если нужен только первый элемент
        $targetSection = reset($targetSection);

        if (empty($targetSection)) {
            return ['status' => 400, 'data' => "Отсутствуют столы в резерве:" . $bankedName];
        }
        //  dd($targetSection);
        $result = array_map(function ($table) {
            return $table['id'];
        }, $targetSection['tables']);
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
                userData: "",
                organizationId: $organizationId
            );
            $result = $this->customerRepository->set($customerDTO);
            if (isset($result['status']) && $result['status'] >= 400) {
                return $result;
            }
            $customerDTO = $this->customerRepository->get($organizationId, $phone);

        }

        // If
        return $customerDTO;
    }

    private function setTable($organizationsId, $terminalGroupId, $customer, $phone, $tables, $dateVisit, $durationInMinutes, $customerCount): array
    {

        return $this->tableRepository->set($organizationsId, $terminalGroupId, $customer, $phone, $tables, $dateVisit, $durationInMinutes, $customerCount);
    }

}