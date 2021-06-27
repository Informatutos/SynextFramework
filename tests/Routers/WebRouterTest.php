<?php

declare(strict_types=1);

namespace synexTest\Routers;

use Synext\Routers\Router;
use Synext\Routers\WebRoute;
use PHPUnit\Framework\TestCase;

class WebRouterTest extends TestCase
{

    public function testmatchMethod()
    {
        $webRouter = new WebRoute(new Router);
        $r = $webRouter->get('/test', '/view/tfd')
                ->matching();

        $this->assertIsArray($r);
    }
}
