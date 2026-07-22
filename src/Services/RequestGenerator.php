<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\Naming;
use Illuminate\Support\Facades\File;

class RequestGenerator
{
    protected ?string $currentModel = null;

    public function create(string $model, array $fields = [], string $base = 'user'): array
    {
        $modelClass = Naming::model($model);
        $this->currentModel = $modelClass;
        $baseConfig = $this->baseConfig($base);

        $relativePath = trim((string) ($baseConfig['request_path'] ?? 'Http/Requests'), '/\\');
        $dir = app_path($relativePath);
        $namespace = rtrim((string) ($baseConfig['request_namespace'] ?? config('blueprint-studio.namespaces.requests', 'App\\Http\\Requests')), '\\');

        File::ensureDirectoryExists($dir);

        $created = [];
        foreach (['Store' => false, 'Update' => true] as $action => $isUpdate) {
            $class = Naming::request($modelClass, $action);
            $path = $dir.DIRECTORY_SEPARATOR.$class.'.php';
            $rules = $this->rulesExport($fields, $isUpdate);
            $content = $this->build($class, $namespace, $rules);
            File::put($path, $content);
            $created[] = [
                'path' => $path,
                'class' => $namespace.'\\'.$class,
                'relative' => $this->relative($path),
                'base' => $base,
            ];
        }

        return $created;
    }

    public function rulesArray(array $fields, bool $isUpdate = false): array
    {
        $types = config('blueprint-studio.field_types', []);
        $rules = [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'string';

            if ($name === '' || in_array($name, ['id', 'timestamps'], true) || in_array($type, ['id', 'timestamps'], true)) {
                continue;
            }

            $meta = $types[$type] ?? ['rules' => ['string']];
            $fieldRules = $meta['rules'] ?? ['string'];

            if ($type === 'foreignId') {
                $table = $field['foreign_table'] ?? Naming::table(str_replace('_id', '', $name));
                $fieldRules = ['integer', "exists:{$table},id"];
            }

            if ($type === 'enum') {
                $values = $field['enum_values'] ?? ['active', 'inactive'];
                if (is_string($values)) {
                    $values = array_values(array_filter(array_map('trim', explode(',', $values))));
                }
                $fieldRules = ['string', 'in:'.implode(',', $values)];
            }

            $required = ($field['nullable'] ?? false)
                ? 'nullable'
                : ($isUpdate ? 'sometimes' : 'required');

            $compiled = array_merge([$required], $fieldRules);

            if (! empty($field['unique'])) {
                $compiled[] = 'unique:'.Naming::table($this->currentModel ?? 'Item').','.$name;
            }

            $rules[$name] = $compiled;
        }

        return $rules;
    }

    protected function rulesExport(array $fields, bool $isUpdate): string
    {
        $rules = $this->rulesArray($fields, $isUpdate);
        if (empty($rules)) {
            return '[]';
        }

        $lines = [];
        foreach ($rules as $field => $fieldRules) {
            $parts = array_map(fn ($r) => "'{$r}'", $fieldRules);
            $lines[] = "            '{$field}' => [".implode(', ', $parts).'],';
        }

        return "[\n".implode("\n", $lines)."\n        ]";
    }

    protected function build(string $class, string $namespace, string $rules): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Foundation\Http\FormRequest;

class {$class} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return {$rules};
    }
}

PHP;
    }

    protected function baseConfig(string $base): array
    {
        $bases = config('blueprint-studio.controller_bases', []);

        return $bases[$base] ?? ($bases['user'] ?? []);
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
