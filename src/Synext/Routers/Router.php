<?php

namespace Synext\Routers;

use Synext\Exceptions\RoutersException;

class Router
{
    /**
     * Contain all routes
     *
     * @var array
     */
    private $routers = [];

    /**
     * Contain all named routes
     *
     * @var array
     */
    private $routersWithName = [];

    /**
     * All matched types for route parameter
     *
     * @var array
     */
    private $routeParamMatcheTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        's' => '^[a-z0-9-]+$',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        'o'   => '[^/\.]++'
    ];


    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $basePath = '';

    /**
     * Maping a route to a target
     *
     * @param string $method Accept one like GET or multiple methods with this syntax GET|POST|PATCH|PUT|DELETE
     * @param string $route You can set custom regex by started line with @ or use pret-set regex like {i:id}
     * @param mixed $target Any target available for route pointer
     * @param string $name Optional route name , Set it if you want to generate a route url
     * @throws RoutersException
     * @return $this
     */

    public function maping(string $method, string $route, $target, string $name = null)
    {
        $this->routers[] = [$method, $route, $target, $name];

        if ($name) {
            if (isset($this->routersWithName[$name])) {
                throw new RoutersException("The route named '{$name}' cannot be redeclared");
            }
            $this->routersWithName[$name] = $route;
        }
        return $this;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    private function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                        . ($pre !== '' ? $pre : null)
                        . '('
                        . ($param !== '' ? "?P<$param>" : null)
                        . $type
                        . ')'
                        . $optional
                        . ')'
                        . $optional;

                $route = str_replace($block, $pattern, $route);
            }
        }
        return "`^$route$`u";
    }


    /**
     * Check if request method match with defind method
     *
     * @param string $method
     * @param string $requestMethod
     * @return boolean
     */
    private function matchMethod(string $method, string $requestMethod): bool
    {
        if (is_bool(strpos($method, $requestMethod))) {
            return false;
        }
        return true;
    }

    private function matchRoute(string $route, string $requestUri, string $lastrequestUriChar)
    {

        if ($route === '*') {
            // * wildcard (matches all)
            return true;
        } elseif (isset($route[0]) && $route[0] === '@') {
            // @ regex delimiter
            $pattern = '`' . substr($route, 1) . '`u';
            return preg_match($pattern, $requestUri, $params) === 1;
        } elseif (($position = strpos($route, '[')) === false) {
            // No params in url, do string comparison
            return strcmp($requestUri, $route) === 0;
        } else {
            /**
             * Compare longest non-param string with url before moving on to regex
             * Check if last character before param is a slash, because it could be optional
             * if param is optional too (see https://github.com/dannyvankooten/AltoRouter/issues/241)
             * if (strncmp($requestUri, $route, $position) !== 0 && ($lastrequestUriChar === '/' ||
             * $route[$position-1] !== '/')) {
             * continue;}
             * $regex = $this->compileRoute($route);
             * $match = preg_match($regex, $requestUri, $params) === 1;
             */

            if (strncmp($requestUri, $route, $position) !== 0
                && ($lastrequestUriChar === '/' || $route[$position-1] !== '/')) {
                $regex = $this->compileRoute($route);
                return preg_match($regex, $requestUri, $params) === 1;
            }
        }
    }

    public function match(string $requestUri = null, string $requestMethod = null)
    {

        $params = [];
        if (is_null($requestUri)) {
            $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        }
        // base path remove from request uri
        $requestUri = substr($requestUri, strlen($this->basePath));

        //set request default mehod to GET if is not defind
        if (is_null($requestMethod)) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }

        if (!is_bool(($strpos = strpos($requestUri, '?')))) {
            $requestUri = substr($requestUri, 0, $strpos);
        }

        $lastrequestUriChar = $requestUri[strlen($requestUri) - 1];

        foreach ($this->routers as $handler) {
            list($method, $route, $target, $name) = $handler;

            if (!$this->matchMethod($method, $requestMethod)) {
                continue;
            }
            $matchRoute = $this->matchRoute($route, $requestUri, $lastrequestUriChar);

            if ($matchRoute) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }
                return [
                    'target' => $target,
                    'params' => $params,
                    'name' => $name
                ];
            }
        }
        return false;
    }
}
