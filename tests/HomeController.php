<?php

declare(strict_types=1);

namespace tests;

use App\Kernel\Controller\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
         $this->view('home');
    }
}