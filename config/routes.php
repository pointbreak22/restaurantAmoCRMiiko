<?php


use App\Kernel\Router\Route;

return [
    Route::get('/', [\App\Controller\HomeController::class, 'index']),
    Route::get('/token', [\App\Controller\HomeController::class, 'testToken']),
];