<?php

declare(strict_types=1);

namespace App\Kernel\Container;

use App\Kernel\Http\Request;
use App\Kernel\Http\Response;
use App\Kernel\Router\Router;
use App\Kernel\View\View;

readonly class Container
{
    public function __construct(
        public readonly Request $request,
        public readonly Router $router,
        public readonly View $view,
    ) {
    }

    public static function registerServices(): static
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        $view = new View();
        $router = new Router($request, $response, $view);

        return new static(
            $request,
            $router,
            $view
        );
    }
}