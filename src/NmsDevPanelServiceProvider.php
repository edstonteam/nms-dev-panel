<?php

namespace Egarrido\NmsDevPanel;

use Egarrido\NmsDevPanel\Http\Middleware\ExpireCookies;
use Egarrido\NmsDevPanel\Http\Middleware\InjectDevPanel;
use Egarrido\NmsDevPanel\Services\GitBranchResolver;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class NmsDevPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nms-dev-panel.php', 'nms-dev-panel');
        $this->app->singleton(GitBranchResolver::class, function ($app): GitBranchResolver {
            return new GitBranchResolver($app->basePath());
        });
    }

    public function boot(Router $router): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nms-dev-panel');
        $this->publishes([
            __DIR__.'/../config/nms-dev-panel.php' => config_path('nms-dev-panel.php'),
        ], 'nms-dev-panel-config');

        if (!$this->isEnabled()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $router->prependMiddlewareToGroup('web', ExpireCookies::class);
        $router->pushMiddlewareToGroup('web', InjectDevPanel::class);
    }

    private function isEnabled(): bool
    {
        return (bool) config('nms-dev-panel.enabled')
            && $this->app->environment(config('nms-dev-panel.environments', ['local']));
    }
}
