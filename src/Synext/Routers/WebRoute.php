<?php

namespace Synext\Routers;

use Synext\Routers\Router;

//implements RouterInterface use synexTest\Routers\RouterInterface;
class WebRoute
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function get(string $route, string $target, ?string $name = null): self
    {
        $this->router->maping('GET', $route, $target);

        return $this;
    }

    public function post(string $route, string $target, ?string $name = null): Router
    {
        return $this->router;
    }

    public function run() : void
    {
        return ;
    }

    public function matching()
    {
        return $this->router->match();
    }
}
