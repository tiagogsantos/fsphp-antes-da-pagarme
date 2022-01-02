<?php

namespace Source\Core;

/**
 * FSPHP | Class Route
 *
 * @author Robson V. Leite <cursos@upinside.com.br>
 * @package Source\Core
 */
class Route
{
    /** @var array */
    protected static $route;

    /**
     * @param string $route
     * @param $handler
     */
    public static function get(string $route, $handler): void
    {
        $get = "/" . filter_input(INPUT_GET, "url", FILTER_SANITIZE_SPECIAL_CHARS);
        self::$route = [
            $route => [
                "route" => $route,
                "controller" => (!is_string($handler) ? $handler : strstr($handler, ":", true)),
                "method" => (!is_string($handler)) ?: str_replace(":", "", strstr($handler, ":", false))
            ]
        ];

        self::dispatch($get);
    }

    /**
     * @param $route
     */
    public static function dispatch($route): void
    {
        $route = (self::$route[$route] ?? []);

        if ($route) {
            if ($route['controller'] instanceof \Closure) {
                call_user_func($route['controller']);
                return;
            }

            $controller = self::namespace() . $route['controller'];
            $method = $route['method'];

            if (class_exists($controller)) {
                $newController = new $controller;
                if (method_exists($controller, $method)) {
                    $newController->$method();
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function routes(): array
    {
        return self::$route;
    }

    /**
     * @return string
     */
    private static function namespace(): string
    {
        return "Source\App\Controllers\\";
    }
}