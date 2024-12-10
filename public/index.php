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