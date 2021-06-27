<?php
namespace synexTest\Routers;

use Synext\Routers\Router;

interface RouterInterface
{
    public function get(string $route, string $target, ?string $name = null) : Router;

    public function post(string $route, string $target, ?string $name = null) : Router;

    public function run() : void;
}
