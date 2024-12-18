<?php


use App\Kernel\Router\Route;

return [
    Route::get('/', [\App\Controller\HomeController::class, 'index']),
    // Route::get('/token', [\App\Controller\HomeController::class, 'testToken']),
    // Route::get('/amoCRM', [\App\Controller\HomeController::class, 'testAmoCrm']),
    Route::get('/auth/callback', [\App\Controller\HomeController::class, 'handleCallback']), // добавлен маршрут для /oauth/callback
    Route::post('/webhook/handler', [\App\Controller\WebhookController::class, 'handleWebhook'])

];