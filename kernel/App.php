<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Kernel\Container\Container;

class App
{
    private Container $container;

    public function __construct()
    {
        $this->container = Container::registerServices();
    }

    public function run(): void
    {
        $this->container->router->dispath(
            $this->container->request->uri(),
            $this->container->request->method()
        );
    }
}