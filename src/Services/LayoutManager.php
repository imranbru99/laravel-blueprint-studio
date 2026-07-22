<?php

namespace Imran\BlueprintStudio\Services;

use Illuminate\Support\Facades\File;

class LayoutManager
{
    public function ensure(): array
    {
        $layoutPath = config('blueprint-studio.layout.path', 'layouts.app');
        $autoCreate = (bool) config('blueprint-studio.layout.auto_create', true);
        $blade = resource_path('views/'.str_replace('.', '/', $layoutPath).'.blade.php');

        if (File::exists($blade)) {
            return [
                'exists' => true,
                'created' => false,
                'path' => $blade,
                'view' => $layoutPath,
                'relative' => $this->relative($blade),
            ];
        }

        if (! $autoCreate) {
            return [
                'exists' => false,
                'created' => false,
                'path' => $blade,
                'view' => $layoutPath,
                'relative' => $this->relative($blade),
            ];
        }

        File::ensureDirectoryExists(dirname($blade));
        File::put($blade, $this->stub());

        return [
            'exists' => true,
            'created' => true,
            'path' => $blade,
            'view' => $layoutPath,
            'relative' => $this->relative($blade),
        ];
    }

    public function status(): array
    {
        $layoutPath = config('blueprint-studio.layout.path', 'layouts.app');
        $blade = resource_path('views/'.str_replace('.', '/', $layoutPath).'.blade.php');

        return [
            'view' => $layoutPath,
            'exists' => File::exists($blade),
            'path' => File::exists($blade) ? $this->relative($blade) : null,
        ];
    }

    protected function stub(): string
    {
        $stub = __DIR__.'/../../resources/stubs/layout.blade.php';

        if (File::exists($stub)) {
            return File::get($stub);
        }

        return <<<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <main>@yield('content')</main>
</body>
</html>
BLADE;
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
