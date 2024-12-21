<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('APP_PATH', dirname(__DIR__));
require_once APP_PATH . '/vendor/autoload.php';

use App\Kernel\App;
use Symfony\Component\Dotenv\Dotenv;

try {
    //dd($_SERVER);
    $dotenv = new Dotenv();
    $dotenv->loadEnv(APP_PATH . '/.env');

    define("APP_ENV", $_SERVER['APP_ENV']);
    define("APP_URL", $_SERVER['APP_URL']);
    define("APP_PROJECT", $_SERVER['APP_PROJECT']);
    define("IIKO_API_KEY", $_SERVER['IIKO_API_KEY']);


    define("AMO_CLIENT_ID", $_SERVER['AMO_CLIENT_ID']);
    define("AMO_CLIENT_SECRET", $_SERVER['AMO_CLIENT_SECRET']);


    //define("HOST_SERVER", $_SERVER['SERVER_NAME'] . $_SERVER['REDIRECT_URL']);
    //dd($_SERVER);

    //  dd(HOST_SERVER);

    //define("HOST_SERVER", $_SERVER['HTTP_X_FORWARDED_HOST']);
    define("BASE_DOMAIN", $_SERVER['BASE_DOMAIN']);


    $app = new App();
    $app->run();
} catch (Throwable $th) {
    header("Content-Type: application/json");
    echo json_encode([
        'code' => $th->getCode(),
        'message' => $th->getMessage(),
        'file' => $th->getFile(),
        'line' => $th->getLine(),
    ], JSON_PRETTY_PRINT);
}