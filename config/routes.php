<?php


use App\Kernel\Router\Route;
use App\Tests\AmoCrmTokenTest;
use App\Tests\CreateReserveIIKO;
use App\Tests\IikoTokenTest;
use App\Tests\LogTest;

return [

    Route::get('/amocrm-iiko/', [\App\Controller\HomeController::class, 'index']),
    Route::post('/amocrm-iiko/webhook/handler', [\App\Controller\QueueController::class, 'addToQueue']),


    // Tests
    Route::get('/amocrm-iiko/test/banquet', [\App\Controller\BanquetController::class, 'readQueue']),
    Route::get('/amocrm-iiko/test/tokenAMOCRM', [AmoCrmTokenTest::class, 'index']),
    Route::get('/amocrm-iiko/test/tokenIIKO', [IikoTokenTest::class, 'index']),
    Route::get('/amocrm-iikotest/log', [LogTest::class, 'index']),
    Route::get('/amocrm-iiko/test/reserve', [CreateReserveIIKO::class, 'index']),

];