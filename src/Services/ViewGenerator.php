<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\Naming;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ViewGenerator
{
    public function create(string $model, array $fields = [], string $base = 'user'): array
    {
        $modelClass = Naming::model($model);
        $bases = config('blueprint-studio.controller_bases', []);
        $baseConfig = $bases[$base] ?? $bases['user'];
        $viewPrefix = str_replace('.', DIRECTORY_SEPARATOR, rtrim($baseConfig['view_prefix'] ?? '', '.'));
        $folder = Naming::viewFolder($modelClass);
        $viewsPath = config('blueprint-studio.paths.views');

        $dir = $viewPrefix
            ? $viewsPath.DIRECTORY_SEPARATOR.$viewPrefix.DIRECTORY_SEPARATOR.$folder
            : $viewsPath.DIRECTORY_SEPARATOR.$folder;

        File::ensureDirectoryExists($dir);

        $layout = config('blueprint-studio.layout.path', 'layouts.app');
        $section = config('blueprint-studio.layout.section', 'content');
        $titleSection = config('blueprint-studio.layout.title_section', 'title');

        $route = Naming::route($modelClass);
        $routePrefix = $baseConfig['route_prefix'] ?? '';
        $routeNames = $routePrefix.$route;
        $var = Naming::variable($modelClass);
        $vars = Naming::variables($modelClass);
        $label = Str::title(str_replace(['_', '-'], ' ', $folder));
        $editable = $this->editableFields($fields);

        $files = [
            'index' => $this->indexView($layout, $section, $titleSection, $label, $var, $vars, $routeNames, $editable),
            'create' => $this->formView($layout, $section, $titleSection, $label, $var, $routeNames, $editable, false),
            'edit' => $this->formView($layout, $section, $titleSection, $label, $var, $routeNames, $editable, true),
            'show' => $this->showView($layout, $section, $titleSection, $label, $var, $routeNames, $editable),
            '_form' => $this->partialForm($editable, $var),
        ];

        $created = [];
        foreach ($files as $name => $content) {
            $path = $dir.DIRECTORY_SEPARATOR.$name.'.blade.php';
            File::put($path, $content);
            $created[] = [
                'name' => $name,
                'path' => $path,
                'relative' => $this->relative($path),
            ];
        }

        return $created;
    }

    protected function editableFields(array $fields): array
    {
        return array_values(array_filter($fields, function ($field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? '';

            return $name !== ''
                && ! in_array($name, ['id', 'timestamps'], true)
                && ! in_array($type, ['id', 'timestamps'], true);
        }));
    }

    protected function indexView(string $layout, string $section, string $title, string $label, string $var, string $vars, string $routes, array $fields): string
    {
        $headers = '';
        $cells = '';
        foreach (array_slice($fields, 0, 5) as $field) {
            $fname = $field['name'];
            $flabel = Naming::label($fname);
            $headers .= "                        <th class=\"px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500\">{$flabel}</th>\n";
            $cells .= "                            <td class=\"px-4 py-3 text-sm text-slate-700\">{{ \${$var}->{$fname} }}</td>\n";
        }

        return <<<BLADE
@extends('{$layout}')

@section('{$title}', '{$label}')

@section('{$section}')
<div class="mx-auto max-w-6xl px-4 py-8" x-data>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{$label}</h1>
            <p class="mt-1 text-sm text-slate-500">Manage all {$label} records.</p>
        </div>
        <a href="{{ route('{$routes}.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
            Create {$label}
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">ID</th>
{$headers}                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(\${$vars} as \${$var})
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ \${$var}->id }}</td>
{$cells}                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('{$routes}.show', \${$var}) }}" class="rounded-md px-2.5 py-1.5 text-slate-600 hover:bg-slate-100">View</a>
                                    <a href="{{ route('{$routes}.edit', \${$var}) }}" class="rounded-md px-2.5 py-1.5 text-slate-600 hover:bg-slate-100">Edit</a>
                                    <form action="{{ route('{$routes}.destroy', \${$var}) }}" method="POST" onsubmit="return confirm('Delete this record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md px-2.5 py-1.5 text-rose-600 hover:bg-rose-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="px-4 py-12 text-center text-sm text-slate-500">No {$label} found. Create the first one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists(\${$vars}, 'links'))
            <div class="border-t border-slate-100 px-4 py-3">{{ \${$vars}->links() }}</div>
        @endif
    </div>
</div>
@endsection
BLADE;
    }

    protected function formView(string $layout, string $section, string $title, string $label, string $var, string $routes, array $fields, bool $isEdit): string
    {
        $heading = $isEdit ? "Edit {$label}" : "Create {$label}";
        $action = $isEdit
            ? "{{ route('{$routes}.update', \${$var}) }}"
            : "{{ route('{$routes}.store') }}";
        $method = $isEdit ? "@method('PUT')" : '';
        $modelBind = $isEdit ? "\${$var}" : 'null';
        $formInclude = $this->formInclude($routes);

        return <<<BLADE
@extends('{$layout}')

@section('{$title}', '{$heading}')

@section('{$section}')
<div class="mx-auto max-w-2xl px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('{$routes}.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back to {$label}</a>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{$heading}</h1>
    </div>

    <form action="{$action}" method="POST" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data>
        @csrf
        {$method}
        @include('{$formInclude}', ['model' => {$modelBind}])

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
            <a href="{{ route('{$routes}.index') }}" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                {$heading}
            </button>
        </div>
    </form>
</div>
@endsection
BLADE;
    }

    protected function formInclude(string $routes): string
    {
        $parts = explode('.', $routes);
        $folder = array_pop($parts);
        $prefix = count($parts) ? implode('.', $parts).'.' : '';

        return $prefix.$folder.'._form';
    }

    protected function showView(string $layout, string $section, string $title, string $label, string $var, string $routes, array $fields): string
    {
        $rows = '';
        foreach ($fields as $field) {
            $fname = $field['name'];
            $flabel = Naming::label($fname);
            $rows .= <<<HTML
        <div class="grid grid-cols-3 gap-4 border-b border-slate-100 py-4">
            <dt class="text-sm font-medium text-slate-500">{$flabel}</dt>
            <dd class="col-span-2 text-sm text-slate-900">{{ \${$var}->{$fname} }}</dd>
        </div>

HTML;
        }

        return <<<BLADE
@extends('{$layout}')

@section('{$title}', '{$label} Details')

@section('{$section}')
<div class="mx-auto max-w-2xl px-4 py-8">
    <div class="mb-8 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('{$routes}.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back</a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{$label} #{{ \${$var}->id }}</h1>
        </div>
        <a href="{{ route('{$routes}.edit', \${$var}) }}" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">Edit</a>
    </div>

    <dl class="rounded-xl border border-slate-200 bg-white px-6 py-2 shadow-sm">
{$rows}        <div class="grid grid-cols-3 gap-4 py-4">
            <dt class="text-sm font-medium text-slate-500">Created</dt>
            <dd class="col-span-2 text-sm text-slate-900">{{ \${$var}->created_at }}</dd>
        </div>
    </dl>
</div>
@endsection
BLADE;
    }

    protected function partialForm(array $fields, string $var): string
    {
        $inputs = '';
        foreach ($fields as $field) {
            $inputs .= $this->inputBlock($field, $var)."\n";
        }

        return trim($inputs)."\n";
    }

    protected function inputBlock(array $field, string $var): string
    {
        $name = $field['name'];
        $type = $field['type'] ?? 'string';
        $label = Naming::label($name);
        $types = config('blueprint-studio.field_types', []);
        $input = $types[$type]['input'] ?? 'text';
        $required = empty($field['nullable']) ? 'required' : '';

        $value = "{{ old('{$name}', optional(\$model)->{$name}) }}";

        if ($input === 'textarea') {
            return <<<BLADE
<div>
    <label for="{$name}" class="mb-1.5 block text-sm font-medium text-slate-700">{$label}</label>
    <textarea name="{$name}" id="{$name}" rows="4" {$required}
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{$value}</textarea>
    @error('{$name}')<p class="mt-1 text-sm text-rose-600">{{ \$message }}</p>@enderror
</div>
BLADE;
        }

        if ($input === 'checkbox') {
            return <<<BLADE
<div class="flex items-center gap-3">
    <input type="hidden" name="{$name}" value="0">
    <input type="checkbox" name="{$name}" id="{$name}" value="1"
           {{ old('{$name}', optional(\$model)->{$name}) ? 'checked' : '' }}
           class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
    <label for="{$name}" class="text-sm font-medium text-slate-700">{$label}</label>
    @error('{$name}')<p class="mt-1 text-sm text-rose-600">{{ \$message }}</p>@enderror
</div>
BLADE;
        }

        if ($input === 'select' || $type === 'enum') {
            $values = $field['enum_values'] ?? ['active', 'inactive'];
            if (is_string($values)) {
                $values = array_values(array_filter(array_map('trim', explode(',', $values))));
            }
            $options = '';
            foreach ($values as $opt) {
                $options .= "        <option value=\"{$opt}\" {{ old('{$name}', optional(\$model)->{$name}) == '{$opt}' ? 'selected' : '' }}>".Naming::label($opt)."</option>\n";
            }

            return <<<BLADE
<div>
    <label for="{$name}" class="mb-1.5 block text-sm font-medium text-slate-700">{$label}</label>
    <select name="{$name}" id="{$name}" {$required}
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <option value="">Select {$label}</option>
{$options}    </select>
    @error('{$name}')<p class="mt-1 text-sm text-rose-600">{{ \$message }}</p>@enderror
</div>
BLADE;
        }

        $htmlType = match ($input) {
            'email' => 'email',
            'password' => 'password',
            'number' => 'number',
            'date' => 'date',
            'datetime-local' => 'datetime-local',
            'time' => 'time',
            default => 'text',
        };

        $valueAttr = $htmlType === 'password' ? '' : "value=\"{$value}\"";

        return <<<BLADE
<div>
    <label for="{$name}" class="mb-1.5 block text-sm font-medium text-slate-700">{$label}</label>
    <input type="{$htmlType}" name="{$name}" id="{$name}" {$required} {$valueAttr}
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
    @error('{$name}')<p class="mt-1 text-sm text-rose-600">{{ \$message }}</p>@enderror
</div>
BLADE;
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
