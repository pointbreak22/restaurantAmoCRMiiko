<?php

declare(strict_types=1);

namespace App\Controller;


use App\DTO\HookDataDTO;
use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\IIKO\Core\IikoTokenService;
use App\Service\IikoTableReservationService;
use App\Service\LoggingService;
use Exception;
use Random\RandomException;


class HomeController extends Controller
{


    private IikoTableReservationService $reservationService;
    private AmoAuthService $amoAuthService;


    function __construct()
    {
        $this->reservationService = new IikoTableReservationService();
        $this->amoAuthService = new  AmoAuthService();
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function index(): void
    {
        LoggingService::save("errrrrrrrr", "message", "webhook");


        exit;

        $result = (new IikoTokenService())->getNewToken();

        if (isset($result['status']) && $result['status'] >= 400) {
            //    return $result;
            echo "Ошибка токена: " . $result['data']['errorDescription'] . "<br>";
        } else {
            //       echo "token: " . $result . "<br>";
        }
        $result = $this->amoAuthService->initializeToken();

    }

    /**
     * @throws Exception
     */
    public function iiko(): void
    {

        $hookDataDTO = new HookDataDTO();
        $hookDataDTO->setDataReserve('2024-12-20 14:15:22.123');
        $hookDataDTO->setTimeReserve('180');
        $hookDataDTO->setCountPeople('4');
        $hookDataDTO->setNameReserve("Знахарь");
        $hookDataDTO->setContactName('DEEDEDDEEDEe');
        $hookDataDTO->setContactPhone('998765423332');


        //dd($hookDataDTO);
        $ikoTableReservationService = new IikoTableReservationService();
        $result = $ikoTableReservationService->execute($hookDataDTO);
        dd($result);

    }

    public function handleCallback(): void
    {

        $result = $this->amoAuthService->callback();

        //  dd($result);
    }
}