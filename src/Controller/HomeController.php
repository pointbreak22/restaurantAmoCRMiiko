<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\IIKO\Core\IikoTokenService;
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

}