<?php
namespace System\Core;

class Router
{
    protected $routes = [];
    protected $protectedRoutes = [];

    public function get($uri, $action, $protected = false)
    {
        $this->addRoute('GET', $uri, $action, $protected);
    }

    public function post($uri, $action, $protected = false)
    {
        $this->addRoute('POST', $uri, $action, $protected);
    }

    protected function addRoute($method, $uri, $action, $protected)
    {
        $uri = trim($uri, '/');
        if ($protected) {
            $this->protectedRoutes[$method][$uri] = $action;
        } else {
            $this->routes[$method][$uri] = $action;
        }
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if ($this->isAuthenticated() && in_array($requestUri, ['login', 'register'])) {
            header('Location: /dashboard');
            exit;
        }
        // Merge protected and regular routes for matching
        $allRoutes = array_merge(
            $this->protectedRoutes[$requestMethod] ?? [],
            $this->routes[$requestMethod] ?? []
        );

        foreach ($allRoutes as $route => $action) {
            $routePattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $route);
            if (preg_match('#^' . $routePattern . '$#', $requestUri, $matches)) {
                array_shift($matches); // Remove full match
                // If protected, check authentication
                if (isset($this->protectedRoutes[$requestMethod][$route]) && !$this->isAuthenticated()) {
                    header('Location: /login');
                    exit;
                }
                $this->callAction($action, $matches);
                return;
            }
        }

        http_response_code(404);
        require_once __DIR__ . '/../404page.php';
    }

    protected function callAction($action, $params = [])
    {
        list($controller, $method) = explode('@', $action);
        $controller = "Controllers\\$controller";
        call_user_func_array([new $controller(), $method], $params);
    }

    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }
}