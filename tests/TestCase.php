<?php

namespace Edstonteam\NmsDevPanel\Tests;

use Edstonteam\NmsDevPanel\NmsDevPanelServiceProvider;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [NmsDevPanelServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('nms-dev-panel.environments', ['testing']);
        $app['config']->set('session.driver', 'array');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->get('/html', function () {
            return response('<html><body><main>Application</main></body></html>', 200, ['Content-Type' => 'text/html']);
        });

        $router->middleware('web')->get('/json', function () {
            return response()->json(['ok' => true]);
        });
    }
}
