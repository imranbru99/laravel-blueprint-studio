<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\Naming;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
{
    public function create(string $model, array $fields = [], array $options = []): array
    {
        $modelClass = Naming::model($model);
        $table = Naming::table($modelClass);
        $filename = Naming::migrationFile($modelClass);
        $dir = config('blueprint-studio.paths.migrations');
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        $softDeletes = (bool) ($options['soft_deletes'] ?? config('blueprint-studio.soft_deletes', false));

        File::ensureDirectoryExists($dir);

        // Remove older create_* migrations for same table to avoid duplicates on regenerate
        if (! empty($options['replace_existing'])) {
            $this->removeExistingCreateMigrations($table);
        }

        $content = $this->build($table, $fields, $softDeletes);
        File::put($path, $content);

        return [
            'path' => $path,
            'table' => $table,
            'filename' => $filename,
            'relative' => $this->relative($path),
        ];
    }

    public function update(string $model, array $fields = [], array $options = []): array
    {
        $table = Naming::table($model);
        $existing = $this->findCreateMigration($table);

        if (! $existing) {
            return $this->create($model, $fields, $options);
        }

        $softDeletes = (bool) ($options['soft_deletes'] ?? config('blueprint-studio.soft_deletes', false));
        $content = $this->build($table, $fields, $softDeletes);
        File::put($existing, $content);

        return [
            'path' => $existing,
            'table' => $table,
            'filename' => basename($existing),
            'relative' => $this->relative($existing),
            'updated' => true,
        ];
    }

    public function findCreateMigration(string $table): ?string
    {
        $dir = config('blueprint-studio.paths.migrations');
        if (! File::isDirectory($dir)) {
            return null;
        }

        $pattern = '*_create_'.$table.'_table.php';
        $matches = File::glob($dir.DIRECTORY_SEPARATOR.$pattern) ?: [];

        // Prefer the newest migration if multiples somehow exist
        if (count($matches) > 1) {
            usort($matches, fn ($a, $b) => strcmp(basename($b), basename($a)));
            $keep = array_shift($matches);
            foreach ($matches as $dup) {
                File::delete($dup);
            }

            return $keep;
        }

        return $matches[0] ?? null;
    }

    protected function removeExistingCreateMigrations(string $table): void
    {
        $dir = config('blueprint-studio.paths.migrations');
        $matches = File::glob($dir.DIRECTORY_SEPARATOR.'*_create_'.$table.'_table.php') ?: [];
        foreach ($matches as $file) {
            File::delete($file);
        }
    }

    protected function build(string $table, array $fields, bool $softDeletes): string
    {
        $columns = $this->columnLines($fields, $softDeletes);
        $columnsBlock = implode("\n", $columns);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
{$columnsBlock}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
    }

    protected function columnLines(array $fields, bool $softDeletes): array
    {
        $lines = [];
        $hasId = false;
        $hasTimestamps = false;

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'string';

            if ($name === 'id' || $type === 'id') {
                $lines[] = '            $table->id();';
                $hasId = true;
                continue;
            }

            if ($name === 'timestamps' || $type === 'timestamps') {
                $lines[] = '            $table->timestamps();';
                $hasTimestamps = true;
                continue;
            }

            $lines[] = '            '.$this->columnStatement($field);
        }

        if (! $hasId) {
            array_unshift($lines, '            $table->id();');
        }

        if ($softDeletes) {
            $lines[] = '            $table->softDeletes();';
        }

        if (! $hasTimestamps) {
            $lines[] = '            $table->timestamps();';
        }

        return $lines;
    }

    protected function columnStatement(array $field): string
    {
        $name = $field['name'];
        $type = $field['type'] ?? 'string';
        $nullable = (bool) ($field['nullable'] ?? false);
        $unique = (bool) ($field['unique'] ?? false);
        $default = $field['default'] ?? null;
        $length = $field['length'] ?? null;

        $chain = match ($type) {
            'string', 'email', 'password' => $length
                ? "\$table->string('{$name}', {$length})"
                : "\$table->string('{$name}')",
            'text' => "\$table->text('{$name}')",
            'longText' => "\$table->longText('{$name}')",
            'integer' => "\$table->integer('{$name}')",
            'bigInteger' => "\$table->bigInteger('{$name}')",
            'boolean' => "\$table->boolean('{$name}')",
            'decimal' => "\$table->decimal('{$name}', 12, 2)",
            'float' => "\$table->float('{$name}')",
            'date' => "\$table->date('{$name}')",
            'dateTime' => "\$table->dateTime('{$name}')",
            'time' => "\$table->time('{$name}')",
            'json' => "\$table->json('{$name}')",
            'uuid' => "\$table->uuid('{$name}')",
            'foreignId' => $this->foreignIdStatement($field),
            'enum' => $this->enumStatement($field),
            default => "\$table->string('{$name}')",
        };

        if ($type !== 'foreignId') {
            if ($nullable) {
                $chain .= '->nullable()';
            }
            if ($unique) {
                $chain .= '->unique()';
            }
            if ($default !== null && $default !== '') {
                $chain .= $this->defaultClause($default, $type);
            }
        }

        return $chain.';';
    }

    protected function foreignIdStatement(array $field): string
    {
        $name = $field['name'];
        $table = $field['foreign_table'] ?? Str::plural(Str::beforeLast($name, '_id'));
        $onDelete = $field['on_delete'] ?? 'cascade';
        $nullable = (bool) ($field['nullable'] ?? false);

        $stmt = "\$table->foreignId('{$name}')";
        if ($nullable) {
            $stmt .= '->nullable()';
        }
        $stmt .= "->constrained('{$table}')->onDelete('{$onDelete}')";

        return $stmt;
    }

    protected function enumStatement(array $field): string
    {
        $name = $field['name'];
        $values = $field['enum_values'] ?? ['active', 'inactive'];
        if (is_string($values)) {
            $values = array_values(array_filter(array_map('trim', explode(',', $values))));
        }
        $export = collect($values)->map(fn ($v) => "'".addslashes($v)."'")->implode(', ');

        return "\$table->enum('{$name}', [{$export}])";
    }

    protected function defaultClause(mixed $default, string $type): string
    {
        if ($type === 'boolean') {
            $bool = filter_var($default, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';

            return "->default({$bool})";
        }

        if (is_numeric($default) && ! in_array($type, ['string', 'email', 'password', 'text'], true)) {
            return "->default({$default})";
        }

        return "->default('".addslashes((string) $default)."')";
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
