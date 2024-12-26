<?php

declare(strict_types=1);

namespace App\Controller;


use App\DTO\LeadDTO;
use App\Kernel\Controller\Controller;
use App\Service\IIKO\Core\IikoTokenService;
use App\Service\IikoTableReservationService;
use Exception;
use Random\RandomException;


class HomeController extends Controller
{


    /**
     * @throws RandomException
     * @throws Exception
     */
    public function index(): void
    {
        $result = (new IikoTokenService())->getNewToken();
        if (isset($result['status']) && $result['status'] >= 400) {
            echo "Ошибка токена: " . $result['data']['errorDescription'] . "<br>";
        } else {
            echo "Токен iiko работает";
        }
    }

    /**
     * @throws Exception
     */
    public function iiko(): void
    {

        $hookDataDTO = new LeadDTO();
        $hookDataDTO->setDataReserve('2024-12-20 14:15:22.123');
        $hookDataDTO->setTimeReserve('180');
        $hookDataDTO->setCountPeople('4');
        $hookDataDTO->setNameReserve("Знахарь");
        $hookDataDTO->setContactName('DEEDEDDEEDEe');
        $hookDataDTO->setContactPhone('998765423332');


        $ikoTableReservationService = new IikoTableReservationService();
        $result = $ikoTableReservationService->execute($hookDataDTO);
        dd($result);

    }


}