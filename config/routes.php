<?php


use App\Kernel\Router\Route;

return [

    Route::get('/', [\App\Controller\HomeController::class, 'index']),

    Route::get('/auth/callback', [\App\Controller\HomeController::class, 'handleCallback']), // добавлен маршрут для /oauth/callback
    Route::post('/webhook/handler', [\App\Controller\WebhookController::class, 'handleWebhook']),
    Route::get('/iiko', [\App\Controller\HomeController::class, 'iiko']),
];