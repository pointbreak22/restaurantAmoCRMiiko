<?php

declare(strict_types=1);

namespace App\Controller;


use App\DTO\HookDataDTO;
use App\Kernel\Controller\Controller;
use App\Service\AmoCRM\AmoAuthService;
use App\Service\IIKO\Core\IikoTokenService;
use App\Service\IikoTableReservationService;
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
//        $pattern = '/с (\d{1,2}:\d{2}) до (\d{1,2}:\d{2})/';
//
//
//        $date = 1735126200;
//
//        $date = \DateTime::createFromFormat('U.u', $date . '.0');//->setTime(0, 0, 0);
//
//
//        $string = "с 07:00 до 9:00";
//
//        preg_match($pattern, $string, $matches);
//
//
//        $startTime = $matches[1];
//        $endTime = $matches[2];
//
//
//        $date1 = clone $date;
//        $date2 = clone $date;
//
//
//        $date1->modify($startTime);
//        $date2->modify($endTime);
//
//
//        dd($date1->format('Y-m-d H:i:s.v'), $date2->format('Y-m-d H:i:s.v'));
        //dd("sdss");
        $result = (new IikoTokenService())->getNewToken();


        //  dd($result);

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
//        $result = (new IikoTokenService())->getToken();
//
//
//        if (isset($result['token']))
//            echo "token: " . $result['token'] . "\n";
//        elseif (isset($result['status']) && $result['status'] >= 400) {
//            //    return $result;
//            echo "Ошибка токена: " . $result['data']['errorDescription'] . "<br>";
//        }


        $hookDataDTO = new HookDataDTO();
        $hookDataDTO->setDataReserve('2024-12-20 14:15:22.123');
        $hookDataDTO->setTimeReserve('180');
        $hookDataDTO->setCountPeople('4');
        $hookDataDTO->setNameReserve("Знахарь");
        //  $hookDataDTO->setCreatedReserve($createdReserve);
        //$hookDataDTO->setIdReserve($IdReserve);

        //  $hookDataDTO->setContactEmail('zzzzzzzz@mail.ru');
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