<?php

use App\Kernel\Router\Route;

return [
    Route::get('/', [\App\Controller\HomeController::class, 'index']),
];