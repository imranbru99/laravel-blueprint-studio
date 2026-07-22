<?php

namespace Imran\BlueprintStudio;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Imran\BlueprintStudio\Services\BlueprintOrchestrator;
use Imran\BlueprintStudio\Services\ControllerGenerator;
use Imran\BlueprintStudio\Services\HistoryService;
use Imran\BlueprintStudio\Services\LayoutManager;
use Imran\BlueprintStudio\Services\MigrationGenerator;
use Imran\BlueprintStudio\Services\ModelGenerator;
use Imran\BlueprintStudio\Services\RequestGenerator;
use Imran\BlueprintStudio\Services\RouteRegistrar;
use Imran\BlueprintStudio\Services\ViewGenerator;

class BlueprintStudioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blueprint-studio.php', 'blueprint-studio');

        $this->app->singleton(ModelGenerator::class);
        $this->app->singleton(MigrationGenerator::class);
        $this->app->singleton(ControllerGenerator::class);
        $this->app->singleton(ViewGenerator::class);
        $this->app->singleton(RequestGenerator::class);
        $this->app->singleton(LayoutManager::class);
        $this->app->singleton(HistoryService::class);
        $this->app->singleton(RouteRegistrar::class);
        $this->app->singleton(BlueprintOrchestrator::class);
    }

    public function boot(): void
    {
        if (! config('blueprint-studio.enabled', true)) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blueprint-studio');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/blueprint-studio.php' => config_path('blueprint-studio.php'),
        ], 'blueprint-studio-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/blueprint-studio'),
        ], 'blueprint-studio-views');

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $prefix = config('blueprint-studio.route_prefix', 'blueprint-studio');
        $middleware = array_values(array_filter(array_merge(
            (array) config('blueprint-studio.middleware', ['web']),
            [\Imran\BlueprintStudio\Http\Middleware\EnsureStudioIsEnabled::class]
        )));

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('blueprint-studio.')
            ->group(__DIR__.'/../routes/web.php');
    }
}
