<?php

namespace App\Service\IIKO;


use RuntimeException;

class IikoDataService
{
    private IikoTokenService $tokenService;
    private IikoConnectService $iikoConnectService;

    function __construct()
    {
        $this->tokenService = new IikoTokenService();
        $this->iikoConnectService = new IikoConnectService();
    }

    public function getIikoAvailableRestaurant(): array
    {
        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
        $url = $iikoConfig['url'];
        $controller = $iikoConfig['getAvailableRestaurantSections'];
        $postData = $this->getAvailableRestaurantSectionsParameters();
        return $this->setResponseDataApi($url, $controller, $postData);
    }

    private function getAvailableRestaurantSectionsParameters(): array
    {
        return ['terminalGroupIds' => ["443707ed-5429-e7bd-0187-7433c8ff0064"],
            "returnSchema" => true,
            "revision" => 0];
    }

    public function getIikoRestaurantSectionsWorkload(): array
    {
        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
        $url = $iikoConfig['url'];
        $controller = $iikoConfig['getRestaurantSectionsWorkload'];
        $postData = $this->getRestaurantSectionsWorkloadParameters();

        return $this->setResponseDataApi($url, $controller, $postData);
    }

    private function getRestaurantSectionsWorkloadParameters(): array
    {

        return ['restaurantSectionIds' => ["497f6eca-6276-4993-bfeb-53cbbbba6f08"],
            "dateFrom" => date('Y-m-d 00:00:00'),
            "dateTo" => date('Y-m-d 23:59:59')
        ];
    }

    public function getIikoTerminalGroups(): array
    {
        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
        $url = $iikoConfig['url'];
        $controller = $iikoConfig['getTerminalGroups'];
        $postData = $this->getTerminalGroupsParameters();

        return $this->setResponseDataApi($url, $controller, $postData);
    }

    private function getTerminalGroupsParameters(): array
    {

        return ['restaurantSectionIds' => ["497f6eca-6276-4993-bfeb-53cbbbba6f08"],
            "dateFrom" => date('Y-m-d 00:00:00'),
            "dateTo" => date('Y-m-d 23:59:59')
        ];
    }


    public function getIikoOrganization(): array
    {
        $iikoConfig = (include APP_PATH . '/config/iiko/values.php')['apiSettings'];
        $url = $iikoConfig['url'];
        $controller = $iikoConfig['getOrganizations'];
        $postData = $this->getOrganizationParameters();
        return $this->setResponseDataApi($url, $controller, $postData);
    }

    private function getOrganizationParameters(): array
    {
        $correlationId = $this->tokenService->getCorrelationId();
        //    dd($correlationId);
        return ['organizationIds' => ["497f6eca-6276-4993-bfeb-53cbbbba6f08"],
            "returnAdditionalInfo" => true,
            "includeDisabled" => true,
            "returnExternalData" => ["string"]];
    }

    private function setResponseDataApi($url, $controller, $postData): array
    {
        $token = $this->tokenService->getToken();
        $result = $this->iikoConnectService->ResponseDataApi($url, $controller, $postData, $token);
        if (!isset($result['status'])) {
            throw new RuntimeException('Данные отсутствуют.');
        }
        if ($result['status'] >= 400 && $result['status'] < 500) {
            $token = $this->tokenService->getToken(true);
            $result = $this->iikoConnectService->ResponseDataApi($url, $controller, $postData, $token);
        } elseif ($result['status'] >= 500) {
            // Ошибки сервера
            throw new RuntimeException("Server Error: HTTP Code " . $result['status']);
        }
        return $result;
    }


}