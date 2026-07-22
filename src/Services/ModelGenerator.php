<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\Naming;
use Illuminate\Support\Facades\File;

class ModelGenerator
{
    public function path(string $model): string
    {
        $dir = config('blueprint-studio.paths.models');

        return $dir.DIRECTORY_SEPARATOR.Naming::model($model).'.php';
    }

    public function exists(string $model): bool
    {
        return File::exists($this->path($model));
    }

    public function create(string $model, array $fields = [], array $options = []): array
    {
        $modelClass = Naming::model($model);
        $path = $this->path($modelClass);
        $namespace = config('blueprint-studio.namespaces.models', 'App\\Models');
        $fillable = $this->fillableFields($fields);
        $casts = $this->castFields($fields);
        $softDeletes = (bool) ($options['soft_deletes'] ?? config('blueprint-studio.soft_deletes', false));

        File::ensureDirectoryExists(dirname($path));

        $content = $this->build($modelClass, $namespace, $fillable, $casts, $softDeletes);
        File::put($path, $content);

        return [
            'path' => $path,
            'class' => $namespace.'\\'.$modelClass,
            'relative' => $this->relative($path),
        ];
    }

    public function updateFillable(string $model, array $fields, array $options = []): array
    {
        $path = $this->path($model);

        if (! File::exists($path)) {
            return $this->create($model, $fields, $options);
        }

        // Full rewrite keeps soft deletes / casts consistent and avoids duplicate class leftovers
        return $this->create($model, $fields, $options);
    }

    protected function build(string $model, string $namespace, array $fillable, array $casts, bool $softDeletes): string
    {
        $fillableExport = $this->exportArray($fillable);
        $castsBlock = '';
        $useSoftDeletes = '';
        $trait = '';

        if ($softDeletes) {
            $useSoftDeletes = "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n";
            $trait = "\n    use SoftDeletes;\n";
        }

        if (! empty($casts)) {
            $castsExport = $this->exportAssoc($casts);
            $castsBlock = "\n\n    protected \$casts = {$castsExport};";
        }

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
{$useSoftDeletes}
class {$model} extends Model
{
    use HasFactory;{$trait}

    protected \$fillable = {$fillableExport};{$castsBlock}
}

PHP;
    }

    protected function fillableFields(array $fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? '';
            if ($name === '' || in_array($name, ['id', 'timestamps'], true) || in_array($type, ['id', 'timestamps'], true)) {
                continue;
            }
            $names[] = $name;
        }

        return array_values(array_unique($names));
    }

    protected function castFields(array $fields): array
    {
        $casts = [];
        $types = config('blueprint-studio.field_types', []);

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'string';
            if ($name === '' || in_array($name, ['id', 'timestamps'], true)) {
                continue;
            }
            $cast = $types[$type]['cast'] ?? null;
            if ($cast) {
                $casts[$name] = $cast;
            }
        }

        return $casts;
    }

    protected function exportArray(array $items): string
    {
        if (empty($items)) {
            return '[]';
        }

        $lines = array_map(fn ($i) => "        '{$i}',", $items);

        return "[\n".implode("\n", $lines)."\n    ]";
    }

    protected function exportAssoc(array $items): string
    {
        if (empty($items)) {
            return '[]';
        }

        $lines = [];
        foreach ($items as $key => $value) {
            $lines[] = "        '{$key}' => '{$value}',";
        }

        return "[\n".implode("\n", $lines)."\n    ]";
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
