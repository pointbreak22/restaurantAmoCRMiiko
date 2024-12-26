<?php


use App\Kernel\Router\Route;
use App\Tests\AmoCrmTokenTest;
use App\Tests\IikoTokenTest;
use App\Tests\LogTest;

return [

    Route::get('/', [\App\Controller\HomeController::class, 'index']),
    Route::post('/webhook/handler', [\App\Controller\WebhookController::class, 'handleWebhook']),
    Route::get('/iiko', [\App\Controller\HomeController::class, 'iiko']),

    // Tests
    Route::get('/test/tokenAMOCRM', [AmoCrmTokenTest::class, 'index']),
    Route::get('/test/tokenIIKO', [IikoTokenTest::class, 'index']),
    Route::get('/test/log', [LogTest::class, 'index']),

];