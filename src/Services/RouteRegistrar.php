<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\Naming;
use Illuminate\Support\Facades\File;

class RouteRegistrar
{
    public function register(string $model, string $base = 'user'): array
    {
        if (! config('blueprint-studio.auto_routes', true)) {
            return [
                'registered' => false,
                'skipped' => true,
                'message' => 'Auto routes disabled in config.',
                'hint' => $this->hint($model, $base),
            ];
        }

        $path = base_path('routes/web.php');
        if (! File::exists($path)) {
            return [
                'registered' => false,
                'message' => 'routes/web.php not found.',
                'hint' => $this->hint($model, $base),
            ];
        }

        $route = Naming::route($model);
        $controller = Naming::controller($model);
        $baseConfig = $this->baseConfig($base);
        $fqcn = rtrim($baseConfig['namespace'], '\\').'\\'.$controller;
        $uriPrefix = trim((string) ($baseConfig['uri_prefix'] ?? ''), '/');
        $routeNamePrefix = rtrim((string) ($baseConfig['route_prefix'] ?? ''), '.');
        $marker = "BlueprintStudio:{$base}:{$route}";

        $contents = File::get($path);

        if (str_contains($contents, $marker)) {
            return [
                'registered' => false,
                'updated' => false,
                'exists' => true,
                'relative' => 'routes/web.php',
                'message' => "Route for {$route} ({$base}) already registered.",
                'hint' => $this->hint($model, $base),
                'uri' => $this->uri($route, $base),
                'base' => $base,
            ];
        }

        $block = $this->routeBlock($route, $fqcn, $uriPrefix, $routeNamePrefix, $marker);
        File::put($path, rtrim($contents)."\n\n".$block."\n");

        return [
            'registered' => true,
            'updated' => true,
            'relative' => 'routes/web.php',
            'message' => "Route resource '{$route}' added under ".($uriPrefix !== '' ? "/{$uriPrefix}" : '/').'.',
            'hint' => $this->hint($model, $base),
            'uri' => $this->uri($route, $base),
            'base' => $base,
        ];
    }

    public function hint(string $model, string $base): string
    {
        $route = Naming::route($model);
        $controller = Naming::controller($model);
        $baseConfig = $this->baseConfig($base);
        $ns = rtrim($baseConfig['namespace'], '\\').'\\'.$controller;
        $uriPrefix = trim((string) ($baseConfig['uri_prefix'] ?? ''), '/');
        $routeNamePrefix = rtrim((string) ($baseConfig['route_prefix'] ?? ''), '.');

        if ($uriPrefix === '') {
            return "Route::resource('{$route}', \\{$ns}::class);";
        }

        $name = $routeNamePrefix !== '' ? $routeNamePrefix.'.' : $uriPrefix.'.';

        return "Route::prefix('{$uriPrefix}')->name('{$name}')->group(function () {\n    Route::resource('{$route}', \\{$ns}::class);\n});";
    }

    public function uri(string $route, string $base): string
    {
        $baseConfig = $this->baseConfig($base);
        $uriPrefix = trim((string) ($baseConfig['uri_prefix'] ?? ''), '/');

        return $uriPrefix !== '' ? "/{$uriPrefix}/{$route}" : "/{$route}";
    }

    protected function routeBlock(string $route, string $fqcn, string $uriPrefix, string $routeNamePrefix, string $marker): string
    {
        if ($uriPrefix === '') {
            return <<<PHP
// {$marker}
Route::resource('{$route}', \\{$fqcn}::class);
PHP;
        }

        $name = $routeNamePrefix !== '' ? $routeNamePrefix.'.' : $uriPrefix.'.';

        return <<<PHP
// {$marker}
Route::prefix('{$uriPrefix}')->name('{$name}')->group(function () {
    Route::resource('{$route}', \\{$fqcn}::class);
});
PHP;
    }

    protected function baseConfig(string $base): array
    {
        $bases = config('blueprint-studio.controller_bases', []);

        return $bases[$base] ?? ($bases['user'] ?? [
            'namespace' => 'App\\Http\\Controllers\\User',
            'uri_prefix' => 'user',
            'route_prefix' => 'user.',
        ]);
    }
}
