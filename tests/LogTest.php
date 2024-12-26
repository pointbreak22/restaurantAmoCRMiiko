<?php

namespace App\Tests;

use App\Kernel\Controller\Controller;
use App\Service\LoggingService;

class LogTest extends Controller
{
    public function index(): void
    {
        $text = "Hello World!";
        LoggingService::save($text, "info", "webhook"); //нужно для логирования данных сделки
        dd($text);
    }
}