<?php

namespace Rcv\Core\Http\Middleware;

use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

class ModuleMiddlewareManager
{
    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The registered middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The registered route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [];

    /**
     * Create a new middleware manager instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register a middleware group.
     *
     * @param  string  $name
     * @param  array  $middleware
     * @return void
     */
    public function registerMiddlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;
        $this->router->middlewareGroup($name, $middleware);
    }

    /**
     * Register a route middleware.
     *
     * @param  string  $name
     * @param  string  $middleware
     * @return void
     */
    public function registerRouteMiddleware($name, $middleware)
    {
        $this->routeMiddleware[$name] = $middleware;
        $this->router->aliasMiddleware($name, $middleware);
    }

    /**
     * Get all registered middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Get all registered route middleware.
     *
     * @return array
     */
    public function getRouteMiddleware()
    {
        return $this->routeMiddleware;
    }

    /**
     * Check if a middleware group exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return isset($this->middlewareGroups[$name]);
    }

    /**
     * Check if a route middleware exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasRouteMiddleware($name)
    {
        return isset($this->routeMiddleware[$name]);
    }
} 