<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$router = require dirname(__DIR__) . '/router/routes.php';
$reflection = new ReflectionClass($router);

foreach (['routes', 'protectedRoutes'] as $propertyName) {
    $property = $reflection->getProperty($propertyName);
    foreach ($property->getValue($router) as $routes) {
        foreach ($routes as $definition) {
            $action = is_array($definition) ? $definition['action'] : $definition;
            [$controller, $method] = explode('@', $action, 2);
            $class = 'Controllers\\' . $controller;
            if (!class_exists($class) || !method_exists($class, $method)) {
                fwrite(STDERR, "Missing route action: {$action}\n");
                exit(1);
            }
        }
    }
}

echo "Route actions passed\n";
