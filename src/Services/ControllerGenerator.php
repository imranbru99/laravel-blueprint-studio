<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\Naming;
use Illuminate\Support\Facades\File;

class ControllerGenerator
{
    public function create(string $model, array $fields = [], string $base = 'user', array $options = []): array
    {
        $bases = config('blueprint-studio.controller_bases', []);
        $baseConfig = $bases[$base] ?? $bases['user'];
        $modelClass = Naming::model($model);
        $controller = Naming::controller($modelClass);
        $namespace = rtrim($baseConfig['namespace'], '\\');
        $relativePath = trim($baseConfig['path'], '/\\').DIRECTORY_SEPARATOR.$controller.'.php';
        $path = app_path($relativePath);

        File::ensureDirectoryExists(dirname($path));

        $withRequests = (bool) ($options['with_requests'] ?? true);
        $content = $this->build($modelClass, $namespace, $fields, $baseConfig, $withRequests);
        File::put($path, $content);

        return [
            'path' => $path,
            'class' => $namespace.'\\'.$controller,
            'base' => $base,
            'relative' => $this->relative($path),
        ];
    }

    protected function build(string $model, string $namespace, array $fields, array $baseConfig, bool $withRequests): string
    {
        $controller = Naming::controller($model);
        $modelNs = config('blueprint-studio.namespaces.models', 'App\\Models');
        $requestNs = rtrim((string) ($baseConfig['request_namespace'] ?? config('blueprint-studio.namespaces.requests', 'App\\Http\\Requests')), '\\');
        $var = Naming::variable($model);
        $vars = Naming::variables($model);
        $route = Naming::route($model);
        $viewFolder = Naming::viewFolder($model);
        $viewPrefix = $baseConfig['view_prefix'] ?? '';
        $routeNamePrefix = $baseConfig['route_prefix'] ?? '';
        $views = $viewPrefix.$viewFolder;
        $routeNames = $routeNamePrefix.$route;

        $storeRequest = Naming::request($model, 'Store');
        $updateRequest = Naming::request($model, 'Update');

        $storeType = $withRequests ? $storeRequest.' $request' : 'Request $request';
        $updateType = $withRequests ? $updateRequest.' $request' : 'Request $request';

        $requestImports = $withRequests
            ? "use {$requestNs}\\{$storeRequest};\nuse {$requestNs}\\{$updateRequest};"
            : 'use Illuminate\\Http\\Request;';

        $validated = $withRequests
            ? '$request->validated()'
            : '$request->validate($this->rules())';

        $rulesMethod = $withRequests ? '' : $this->inlineRulesMethod($fields);

        $baseControllerImport = "use App\\Http\\Controllers\\Controller;\n";

        return <<<PHP
<?php

namespace {$namespace};

{$baseControllerImport}use {$modelNs}\\{$model};
{$requestImports}
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class {$controller} extends Controller
{
    public function index(): View
    {
        \${$vars} = {$model}::query()->latest()->paginate(15);

        return view('{$views}.index', compact('{$vars}'));
    }

    public function create(): View
    {
        return view('{$views}.create');
    }

    public function store({$storeType}): RedirectResponse
    {
        {$model}::create({$validated});

        return redirect()
            ->route('{$routeNames}.index')
            ->with('success', '{$model} created successfully.');
    }

    public function show({$model} \${$var}): View
    {
        return view('{$views}.show', compact('{$var}'));
    }

    public function edit({$model} \${$var}): View
    {
        return view('{$views}.edit', compact('{$var}'));
    }

    public function update({$updateType}, {$model} \${$var}): RedirectResponse
    {
        \${$var}->update({$validated});

        return redirect()
            ->route('{$routeNames}.index')
            ->with('success', '{$model} updated successfully.');
    }

    public function destroy({$model} \${$var}): RedirectResponse
    {
        \${$var}->delete();

        return redirect()
            ->route('{$routeNames}.index')
            ->with('success', '{$model} deleted successfully.');
    }
{$rulesMethod}}

PHP;
    }

    protected function inlineRulesMethod(array $fields): string
    {
        $rules = app(RequestGenerator::class)->rulesArray($fields, false);
        $export = var_export($rules, true);

        return <<<PHP

    protected function rules(): array
    {
        return {$export};
    }

PHP;
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
