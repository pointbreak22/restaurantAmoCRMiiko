<?php

namespace App\Service;


use App\DTO\CustomerDTO;
use App\DTO\HookDataDTO;
use App\DTO\ReserveDTO;
use App\Repository\IIKO\Reservation\AvailableRestaurantSectionsRepository;
use App\Repository\IIKO\Reservation\CustomerRepository;
use App\Repository\IIKO\Reservation\MenuRepository;
use App\Repository\IIKO\Reservation\OrganizationRepository;
use App\Repository\IIKO\Reservation\ReserveRepository;
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
    private ReserveRepository $tableRepository;

    private IikoTokenService $iikoTokenService;

    private MenuRepository $menuRepository;

    function __construct()
    {
        $this->organizationRepository = new OrganizationRepository();
        $this->terminalGroupRepository = new TerminalGroupRepository();
        $this->availableRestaurantSectionsRepository = new AvailableRestaurantSectionsRepository();
        $this->customerRepository = new CustomerRepository();
        $this->tableRepository = new ReserveRepository();
        $this->iikoTokenService = new IikoTokenService();
        $this->menuRepository = new MenuRepository();
    }

    /**
     * Главная функция резервации стола
     * @throws Exception
     */


    private function checkValue(mixed $value): bool
    {
        if (is_array($value) && isset($value['status']) && $value['status'] >= 300) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    public function execute(HookDataDTO $hookDataDTO)
    {
        $tokenResult = $this->iikoTokenService->getToken();
        if ($this->checkValue($tokenResult)) {
            return $tokenResult;
        }

        $organizationsId = $this->getOrganisationsId($tokenResult);  //получает организацию


        if (isset($organizationsId['status']) && $organizationsId['status'] == 401 && empty($tokenResult['data'])) {
            $tokenResult = $this->iikoTokenService->getNewToken();
            if ($this->checkValue($tokenResult)) {
                return $tokenResult;
            }
            $organizationsId = $this->getOrganisationsId($tokenResult);
        }

        //    return $organizationsId;

        if ($this->checkValue($organizationsId)) {
            return $organizationsId;
        }
        if (count($organizationsId) == 0) {
            return ['status' => 400, 'data' => "Отсутствуют организации"];
        }

        $terminalGroupsId = $this->getTerminalGroupsId([$organizationsId[0]], $tokenResult); // из организации получает термальную группу.

        //  return $terminalGroupsId;

        if ($this->checkValue($terminalGroupsId)) {
            return $terminalGroupsId;
        }
        if (count($terminalGroupsId) == 0) {
            return ['status' => 400, 'data' => "Отсутствуют группы"];
        }


        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupsId[0]], $hookDataDTO->getNameReserve(), $tokenResult); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки
        //return $tables;

        if ($this->checkValue($tables)) {
            return $tables;
        }
        //   return $tables;

        $customerDTO = $this->getCustomer($organizationsId[0], $hookDataDTO->getContactPhone(), $hookDataDTO->getContactName(), $hookDataDTO->getContactEmail(), $tokenResult);
        if ($this->checkValue($customerDTO)) {
            return $customerDTO;
        }

        $productResult = $this->getMenu($organizationsId[0], $tokenResult);
        if ($this->checkValue($productResult)) {
            return $productResult;
        }

        $reserveDTO = new ReserveDTO();
        $reserveDTO->setOrganizationId($organizationsId[0]);
        $reserveDTO->setTerminalGroupId($terminalGroupsId[0]);
        $reserveDTO->setCustomer($customerDTO);
        $reserveDTO->setPhone($hookDataDTO->getContactPhone());
        $reserveDTO->setTables([$tables[0]]);
        $reserveDTO->setDateVisit($hookDataDTO->getDataReserve());
        $reserveDTO->setDurationInMinutes($hookDataDTO->getTimeReserve());
        $reserveDTO->setCustomerCount($hookDataDTO->getCountPeople());
        $reserveDTO->setProductId($productResult);

        $reserveInfoResult = $this->setReserve($reserveDTO, $tokenResult);
        if ($this->checkValue($reserveInfoResult)) {
            return $reserveInfoResult;
        }
        $reserveId = $reserveInfoResult['data']['reserveInfo']['id'];

        $reserveFullResult = $this->getReserve($organizationsId[0], [$reserveId], $tokenResult);


        return $reserveFullResult;
    }

    /**
     * @throws Exception
     */
    private function getOrganisationsId($tokenResult, $name = "Сиберия"): array
    {
        $response = $this->organizationRepository->get($tokenResult);

        //return $response['data'];

        if ($this->checkValue($response)) {
            return $response;
        }

        $result = array_map(function ($organization) {
            return $organization['id'];
        }, array_filter($response['data']['organizations'], function ($organization) use ($name) {
            return isset($organization['name']) && $organization['name'] == $name;
        }));

        return $result;
    }

    /**
     * @throws Exception
     */
    private function getTerminalGroupsId($organization, $tokenResult): array
    {
        $response = $this->terminalGroupRepository->get($organization, $tokenResult);

        //    return $response;
        if ($this->checkValue($response)) {
            return $response;
        }
        //  dd($response);
        $result = array_map(function ($terminalGroup) {
            return $terminalGroup['id'];
        }, $response['data']['terminalGroups'][0]['items']);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function getAvailableRestaurantSectionsId($terminalGroup, $bankedName, $tokenResult): array
    {
        $response = $this->availableRestaurantSectionsRepository->get($terminalGroup, $tokenResult);

        //  return $response;


        if ($this->checkValue($response)) {
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

    /**
     * @throws Exception
     */
    private function getCustomer($organizationId, $phone, $name, $email, $tokenResult): mixed
    {
        $phone = str_starts_with($phone, "+") ? $phone : "+" . $phone;
        $customerDTO = $this->customerRepository->get($organizationId, $phone, $tokenResult);

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

            $result = $this->customerRepository->set($customerDTO, $tokenResult);

            if ($this->checkValue($result)) {
                return $result;
            }

            //   return $result;
            $customerDTO = $this->customerRepository->get($organizationId, $phone, $tokenResult);

        }
        return $customerDTO;
    }

    /**
     * @throws Exception
     */
    private function getMenu($organizationsId, $tokenResult): array|string|null
    {
        $result = $this->menuRepository->get($organizationsId, $tokenResult);
        if ($this->checkValue($result)) {
            return $result;
        }
        $foundId = null;
        foreach ($result['data']['products'] as $item) {
            if (isset($item['orderItemType']) && $item['orderItemType'] === 'Product') {
                $foundId = $item['id'];
                break; // Останавливаем цикл после нахождения первого подходящего элемента
            }
        }

        return $foundId;
    }

    private function setReserve(ReserveDTO $reserve, string $apiToken): array
    {

        return $this->tableRepository->set($reserve, $apiToken);
    }

    private function getReserve($organizationsId, array $reserves, $tokenResult): array
    {
        return $this->tableRepository->get($organizationsId, $reserves, $tokenResult);
    }

}