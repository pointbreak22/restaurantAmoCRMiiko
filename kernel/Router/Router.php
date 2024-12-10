<?php

declare(strict_types=1);

namespace App\Kernel\Router;

use App\Kernel\Http\Request;
use App\Kernel\Http\Response;
use App\Kernel\View\View;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(
        public readonly Request $request,
        public readonly Response $response,
        public readonly View $view
    ) {
        $this->initRoutes();
    }

    public function dispath(string $uri, string $method): void
    {
        $route = $this->findRoute($uri, $method);

        if ($route === null) {
            $this->notFound();
        }

        if (is_array($route->getAction())) {
            [$controller, $action] = $route->getAction();
            $controller = new $controller();

            call_user_func([$controller, 'setRequest'], $this->request);
            call_user_func([$controller, 'setResponse'], $this->response);
            call_user_func([$controller, 'setView'], $this->view);

            call_user_func([$controller, $action]);
        } else {
            call_user_func($route->getAction());
        }
    }

    private function findRoute(string $uri, string $method): ?Route
    {
        if (array_key_exists($uri, $this->routes[$method])) {
            return $this->routes[$method][$uri];
        }

        return null;
    }

    private function notFound(): void
    {
        exit('404 | Not Found');
    }

    private function initRoutes(): void
    {
        $routes = $this->getRoutes();

        if (!empty($routes)) {
            foreach ($routes as $route) {
                $this->routes[$route->getMethod()][$route->getUri()] = $route;
            }
        }
    }

    /**
     * @return Route[]
     */
    private function getRoutes(): array
    {
        return require_once APP_PATH . '/config/routes.php';
    }
}