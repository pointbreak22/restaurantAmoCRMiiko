<?php

namespace App\Service;


use App\DTO\CustomerDTO;
use App\DTO\LeadDTO;
use App\DTO\ReserveDTO;
use App\Repository\IIKO\Reservation\AvailableRestaurantSectionsRepository;
use App\Repository\IIKO\Reservation\CustomerRepository;
use App\Repository\IIKO\Reservation\MenuRepository;
use App\Repository\IIKO\Reservation\OrganizationRepository;
use App\Repository\IIKO\Reservation\PaymentRepository;
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
    private PaymentRepository $paymentRepository;

    function __construct()
    {
        $this->organizationRepository = new OrganizationRepository();
        $this->terminalGroupRepository = new TerminalGroupRepository();
        $this->availableRestaurantSectionsRepository = new AvailableRestaurantSectionsRepository();
        $this->customerRepository = new CustomerRepository();
        $this->tableRepository = new ReserveRepository();
        $this->iikoTokenService = new IikoTokenService();
        $this->menuRepository = new MenuRepository();
        $this->paymentRepository = new PaymentRepository();
    }

    /**
     * @throws Exception
     */
    public function execute(LeadDTO $leadDTO): array
    {

        $token = $this->getToken();
        $organizationsId = $this->getOrganisationsId($token);  //получает организацию
        $terminalGroupsId = $this->getTerminalGroupsId([$organizationsId[0]], $token); // из организации получает термальную группу.
        $paymentId = $this->getPaymentType([$organizationsId[0]], $token); // из организации получает термальную группу.
        $tables = $this->getAvailableRestaurantSectionsId([$terminalGroupsId[0]], $leadDTO->getNameReserve(), $token); //из терминальной группы получает свободные резервы(столы), и выбор ид заявки
        $customerDTO = $this->getCustomer(
            $organizationsId[0],
            $leadDTO->getContactPhone(),
            $leadDTO->getContactName(),
            $leadDTO->getContactEmail(),
            $token
        );
        $productResult = $this->getMenu($organizationsId[0], $token);

        $reserveDTO = new ReserveDTO();
        $reserveDTO->setOrganizationId($organizationsId[0]);
        $reserveDTO->setTerminalGroupId($terminalGroupsId[0]);
        $reserveDTO->setCustomer($customerDTO);
        $reserveDTO->setPhone($leadDTO->getContactPhone());
        $reserveDTO->setTables([$tables[0]]);
        $reserveDTO->setDateVisit($leadDTO->getDataReserve());
        $reserveDTO->setDurationInMinutes($leadDTO->getTimeReserve());
        $reserveDTO->setCustomerCount($leadDTO->getCountPeople());
        $reserveDTO->setProductId($productResult);
        $reserveDTO->setSumReserve($leadDTO->getSumReserve());
        $reserveDTO->setPaymentId($paymentId);
        $reserveId = $this->setReserve($reserveDTO, $token);

        return $this->getReserve($organizationsId[0], [$reserveId], $token);
    }

    /**
     * @throws Exception
     */
    private function getToken(): string
    {
        $token = $this->iikoTokenService->getToken();

        if (empty($token)) {
            throw new Exception('Нет токена');
        }
        return $token;
    }

    /**
     * @throws Exception
     */
    private function getOrganisationsId($tokenResult, $name = "Сиберия"): array
    {

        $organizationResult = $this->organizationRepository->get($tokenResult, true);
        if (empty($organizationResult)) {
            $tokenResult = $this->iikoTokenService->getNewToken();
            if (empty($tokenResult)) {
                throw new Exception("Токен IIKO пустой");
            }
            $organizationResult = $this->organizationRepository->get($tokenResult);
            if (empty($organizationResult) || !isset($organizationResult['organizations'])) {
                throw new Exception("Пустой результат организации");
            }
        }
        $organizationFiltered = array_map(function ($organization) {
            return $organization['id'];
        }, array_filter($organizationResult['organizations'], function ($organization) use ($name) {
            return isset($organization['name']) && $organization['name'] == $name;
        }));
        if (empty($organizationFiltered)) {
            throw new Exception("отсутствует Сиберия");
        }
        return $organizationFiltered;

    }

    /**
     * @throws Exception
     */
    private function getTerminalGroupsId($organization, $tokenResult): array
    {
        $terminalResponse = $this->terminalGroupRepository->get($organization, $tokenResult);
        $terminalGroups = $terminalResponse['terminalGroups'][0]['items'] ?? null;

        if (empty($terminalGroups)) {
            throw new Exception("Пустой результат термальной группы");
        }

        $terminalFiltered = array_map(function ($terminalGroup) {
            return $terminalGroup['id'];
        }, $terminalResponse['terminalGroups'][0]['items']);

        if (empty($terminalFiltered)) {
            throw new Exception("Отсутствуют термальные группы");
        }

        return $terminalFiltered;
    }

    /**
     * @throws Exception
     */
    private function getAvailableRestaurantSectionsId($terminalGroup, $bankedName, $tokenResult): array
    {
        $responseAvailableSections = $this->availableRestaurantSectionsRepository->get($terminalGroup, $tokenResult);

        $restaurantSections = $responseAvailableSections['restaurantSections'] ?? null;
        if (empty($restaurantSections)) {
            throw new Exception("Отсутствуют секции в резерве");
        }
        $targetSection = array_filter($restaurantSections, function ($section) use ($bankedName) {
            return $section['name'] === $bankedName;
        });
        // Если нужен только первый элемент
        $targetSection = reset($targetSection);

        if (empty($targetSection)) {
            throw new  Exception("Отсутствует резерв: " . $bankedName);
        }

        $tables = array_map(function ($table) {
            return $table['id'];
        }, $targetSection['tables']);

        if (empty($tables)) {
            throw new  Exception("Отсутствуют столы резерва: " . $bankedName);
        }

        return $tables;

    }

    /**
     * @throws Exception
     */
    private function getCustomer($organizationId, $phone, $name, $email, $tokenResult): mixed
    {
        $phone = str_starts_with($phone, "+") ? $phone : "+" . $phone;

        $customerDTO = $this->customerRepository->get($organizationId, $phone, $tokenResult, true);

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
            $this->customerRepository->set($customerDTO, $tokenResult);
            $customerDTO = $this->customerRepository->get($organizationId, $phone, $tokenResult);

        }
        return $customerDTO;
    }

    /**
     * @throws Exception
     */
    private function getMenu($organizationsId, $tokenResult): array|string|null
    {
        $resultMenu = $this->menuRepository->get($organizationsId, $tokenResult);
        $menu = $resultMenu['products'] ?? null;
        if (empty($menu)) {
            throw new Exception("Отсутствуют продукты");
        }

        $foundId = null;
        foreach ($menu as $item) {
            if (isset($item['orderItemType']) && $item['orderItemType'] === 'Product') {
                $foundId = $item['id'];
                break; // Останавливаем цикл после нахождения первого подходящего элемента
            }
        }
        if (empty($foundId)) {
            throw new Exception("Не выбран продукт");
        }

        return $foundId;
    }

    /**
     * @throws Exception
     */
    private function setReserve(ReserveDTO $reserve, string $apiToken): string
    {

        $resultReserve = $this->tableRepository->set($reserve, $apiToken);

        $message = $resultReserve["reserveInfo"]['errorInfo']['message'] ?? null;

        if (!empty($message)) {
            throw new Exception($message);
        }

        $idReserve = $resultReserve["reserveInfo"]['id'] ?? null;
        if (empty($idReserve)) {
            throw new Exception("Сбой в создании резерва");
        }
        return $idReserve;

    }

    private function getReserve($organizationsId, array $reserves, $tokenResult): array
    {
        return $this->tableRepository->get($organizationsId, $reserves, $tokenResult);
    }

    /**
     * @throws Exception
     */
    private function getPaymentType($organizationsId, mixed $tokenResult): mixed
    {
        $resultPayment = $this->paymentRepository->get($organizationsId, $tokenResult);

        $paymentTypes = $resultPayment['paymentTypes'] ?? null;

        if (empty($paymentTypes)) {
            throw new Exception("Отсутствуют платежи в IIKO");
        }

        $filtered = array_filter($resultPayment['paymentTypes'], function ($paymentType) {
            return isset($paymentType['code']) && $paymentType['code'] == 'SITE';
        });

        // Получаем id из первого результата (если нужно только одно значение)
        $id = null;

        if (!empty($filtered)) {
            $firstResult = reset($filtered); // Берём первый элемент
            $id = $firstResult['id'] ?? null; // Извлекаем id
        }

        if (empty($id)) {
            throw new Exception("Отсутствуют платеж в IIKO");
        }

        return $id;

    }

}