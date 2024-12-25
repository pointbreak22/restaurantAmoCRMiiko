<?php


use App\Kernel\Router\Route;
use App\Tests\AmoCrmTokenTest;

return [

    Route::get(APP_PROJECT . '/', [\App\Controller\HomeController::class, 'index']),
    Route::get(APP_PROJECT . '/auth/callback', [\App\Controller\HomeController::class, 'handleCallback']), // добавлен маршрут для /oauth/callback
    Route::post(APP_PROJECT . '/webhook/handler', [\App\Controller\WebhookController::class, 'handleWebhook']),
    Route::get(APP_PROJECT . '/iiko', [\App\Controller\HomeController::class, 'iiko']),

    // Tests
    Route::get(APP_PROJECT . '/test/webhook/handler', [AmoCrmTokenTest::class, 'handleWebhook']),

];