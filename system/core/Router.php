<?php

namespace System\Core;

use System\Services\AuthorizationService;

class Router
{
    protected array $routes = [];
    protected array $protectedRoutes = [];

    public function get(string $uri, string $action, bool|string $protected = false): void
    {
        $this->addRoute('GET', $uri, $action, $protected);
    }

    public function post(string $uri, string $action, bool|string $protected = false): void
    {
        $this->addRoute('POST', $uri, $action, $protected);
    }

    protected function addRoute(string $method, string $uri, string $action, bool|string $protected): void
    {
        $uri = trim($uri, '/');
        if ($protected !== false) {
            $this->protectedRoutes[$method][$uri] = ['action' => $action, 'requirement' => $protected];
            return;
        }
        $this->routes[$method][$uri] = $action;
    }

    public function dispatch(): void
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $requestUri = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

        if ($this->isAuthenticated() && ($requestUri === '' || in_array($requestUri, ['login', 'register'], true))) {
            header('Location: /dashboard');
            exit;
        }

        if ($requestMethod === 'POST') {
            $submittedToken = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            $sessionToken = $_SESSION['csrf_token'] ?? '';
            if (!is_string($submittedToken) || !is_string($sessionToken) || $sessionToken === ''
                || !hash_equals($sessionToken, $submittedToken)) {
                http_response_code(419);
                exit('Your session token expired. Refresh the page and try again.');
            }
        }

        $allRoutes = array_merge($this->protectedRoutes[$requestMethod] ?? [], $this->routes[$requestMethod] ?? []);
        foreach ($allRoutes as $route => $definition) {
            $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
            if (!preg_match('#^' . $routePattern . '$#', $requestUri, $matches)) {
                continue;
            }
            array_shift($matches);
            if (is_array($definition)) {
                if (!$this->isAuthenticated()) {
                    header('Location: /login');
                    exit;
                }
                $requirement = $definition['requirement'];
                if (is_string($requirement) && !(new AuthorizationService())->can($requirement)) {
                    http_response_code(403);
                    require __DIR__ . '/../403page.php';
                    return;
                }
                $definition = $definition['action'];
            }
            $this->callAction($definition, $matches);
            return;
        }

        http_response_code(404);
        require __DIR__ . '/../404page.php';
    }

    protected function callAction(string $action, array $params = []): void
    {
        [$controller, $method] = explode('@', $action, 2);
        $controller = "Controllers\\{$controller}";
        call_user_func_array([new $controller(), $method], $params);
    }

    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}
