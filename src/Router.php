<?php
namespace Lettuce\Core;

class Router
{
    protected $routes = [];
    protected $notFoundHandler;
    protected $baseNamespace;

    public function __construct(array $routes = [], $baseNamespace = 'App\\')
    {
        $this->baseNamespace = $baseNamespace;
        $this->loadRoutes($routes);
    }

    protected function loadRoutes(array $routes)
    {
        foreach ($routes as $route) {
            $methods = $route['methods'] ?? ['GET'];
            $this->addRoute($methods, $route['path'], $route['controller']);
        }
    }

    protected function addRoute(array $methods, string $path, string $controller)
    {
        foreach ($methods as $method) {
            $method = strtoupper($method);
            list($controllerClass, $controllerMethod) = explode('::', $controller);
            $controllerClass = $this->baseNamespace . $controllerClass;
            $this->routes[$method][$path] = [
                'handler' => [$controllerClass, $controllerMethod]
            ];
        }
    }

    public function setNotFoundHandler(callable $handler)
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $path = $path === "" ? "/" : $path;

        // Try to match the request to a route
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $routeConfig) {
                $routePattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $route);
                if (preg_match("#^$routePattern$#", $path, $matches)) {
                    array_shift($matches); // Remove the full match from the beginning of the array

                    // Dispatch to the controller
                    list($controllerClass, $controllerMethod) = $routeConfig['handler'];

                    if (!class_exists($controllerClass)) {
                        echo "500 Internal Server Error: $controllerClass Controller class not found.";
                        return;
                    }

                    $controller = new $controllerClass();

                    if (!method_exists($controller, $controllerMethod)) {
                        echo "500 Internal Server Error: Method not found in controller.";
                        return;
                    }

                    call_user_func_array([$controller, $controllerMethod], $matches);
                    return;
                }
            }
        }

        // If no route matches, call the 404 handler
        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
        } else {
            echo "404 Not Found";
        }
    }
}
