<?php

namespace Imran\BlueprintStudio\Support;

/**
 * Parses Laravel Blueprint–style draft YAML (models + optional controllers).
 *
 * Example:
 * models:
 *   Post:
 *     title: string:400
 *     content: longtext
 *     published_at: nullable timestamp
 *     author_id: id foreign
 *     softDeletes
 *
 * controllers:
 *   Post:
 *     resource: web
 */
class BlueprintDraftParser
{
    protected array $typeMap = [
        'string' => 'string',
        'char' => 'string',
        'text' => 'text',
        'mediumtext' => 'text',
        'longtext' => 'longText',
        'integer' => 'integer',
        'int' => 'integer',
        'tinyinteger' => 'integer',
        'smallinteger' => 'integer',
        'mediuminteger' => 'integer',
        'biginteger' => 'bigInteger',
        'bigint' => 'bigInteger',
        'unsignedinteger' => 'integer',
        'unsignedbiginteger' => 'bigInteger',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'decimal' => 'decimal',
        'double' => 'float',
        'float' => 'float',
        'date' => 'date',
        'datetime' => 'dateTime',
        'datetimetz' => 'dateTime',
        'timestamp' => 'dateTime',
        'timestamptz' => 'dateTime',
        'time' => 'time',
        'json' => 'json',
        'jsonb' => 'json',
        'uuid' => 'uuid',
        'email' => 'email',
        'password' => 'password',
        'enum' => 'enum',
        'id' => 'foreignId',
        'foreignid' => 'foreignId',
        'foreignuuid' => 'uuid',
    ];

    public function parse(string $draft): array
    {
        $tree = $this->parseYamlLike($draft);
        $models = [];
        $controllers = [];

        foreach (($tree['models'] ?? []) as $name => $definition) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            if ($definition === null || $definition === '') {
                $definition = [];
            }

            if (! is_array($definition)) {
                continue;
            }

            $models[] = $this->parseModel($name, $definition);
        }

        foreach (($tree['controllers'] ?? []) as $name => $definition) {
            if (! is_string($name)) {
                continue;
            }
            $controllers[$name] = is_array($definition) ? $definition : [];
        }

        return [
            'models' => $models,
            'controllers' => $controllers,
        ];
    }

    protected function parseModel(string $name, array $definition): array
    {
        $fields = [];
        $softDeletes = false;
        $skip = ['relationships', 'indexes', 'meta'];

        foreach ($definition as $key => $value) {
            if (is_int($key)) {
                // shorthand flags: softDeletes
                $flag = strtolower(trim((string) $value));
                if (in_array($flag, ['softdeletes', 'soft_deletes'], true)) {
                    $softDeletes = true;
                }
                continue;
            }

            $column = (string) $key;
            $lower = strtolower($column);

            if (in_array($lower, $skip, true)) {
                continue;
            }

            if ($lower === 'softdeletes' || $lower === 'soft_deletes') {
                $softDeletes = filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === null || $value === '';
                continue;
            }

            if (in_array($lower, ['id', 'timestamps', 'timestampsTz', 'timestampstz'], true)) {
                // Blueprint allows timestamps: false — we keep defaults locked in studio
                continue;
            }

            $fields[] = $this->parseColumn($column, is_string($value) || is_numeric($value) ? (string) $value : '');
        }

        return [
            'name' => $name,
            'soft_deletes' => $softDeletes,
            'fields' => $fields,
        ];
    }

    protected function parseColumn(string $name, string $attributes): array
    {
        $attributes = trim($attributes);
        $tokens = $attributes === '' ? ['string'] : (preg_split('/\s+/', $attributes) ?: ['string']);

        $type = 'string';
        $length = null;
        $nullable = false;
        $unique = false;
        $default = null;
        $enumValues = null;
        $foreignTable = null;

        foreach ($tokens as $token) {
            $lower = strtolower($token);

            if ($lower === 'nullable') {
                $nullable = true;
                continue;
            }
            if ($lower === 'unique') {
                $unique = true;
                continue;
            }
            if ($lower === 'index' || $lower === 'unsigned' || $lower === 'primary') {
                continue;
            }
            if (str_starts_with($lower, 'default:')) {
                $default = substr($token, 8);
                $default = trim($default, "\"'");
                continue;
            }
            if ($lower === 'foreign' || str_starts_with($lower, 'foreign:')) {
                if (str_contains($lower, ':')) {
                    $foreignTable = substr($token, strpos($token, ':') + 1);
                }
                $type = 'foreignId';
                continue;
            }
            if (str_starts_with($lower, 'ondelete:') || str_starts_with($lower, 'onupdate:')) {
                continue;
            }

            // type or type:attr
            [$rawType, $attr] = array_pad(explode(':', $token, 2), 2, null);
            $mapped = $this->typeMap[strtolower($rawType)] ?? null;

            if ($mapped) {
                $type = $mapped;

                if ($attr !== null && $attr !== '') {
                    if ($mapped === 'string') {
                        $length = (int) $attr ?: null;
                    } elseif ($mapped === 'decimal' && str_contains($attr, ',')) {
                        // keep as decimal; precision ignored visually for now
                    } elseif ($mapped === 'enum') {
                        $enumValues = trim($attr, "\"'");
                    } elseif ($mapped === 'foreignId') {
                        $foreignTable = $attr;
                    }
                }

                // id shorthand without foreign
                if (strtolower($rawType) === 'id' && ! $foreignTable) {
                    $type = 'foreignId';
                    if (str_ends_with($name, '_id')) {
                        $foreignTable = rtrim(substr($name, 0, -3), '_').'s';
                    }
                }
                continue;
            }
        }

        // name heuristics if still string and empty attrs
        if ($attributes === '' || $type === 'string') {
            if (str_ends_with($name, '_id')) {
                $type = 'foreignId';
            } elseif (str_contains(strtolower($name), 'email')) {
                $type = 'email';
            } elseif (str_contains(strtolower($name), 'password')) {
                $type = 'password';
            }
        }

        return [
            'name' => $name,
            'type' => $type,
            'nullable' => $nullable,
            'unique' => $unique,
            'default' => $default,
            'length' => $length,
            'enum_values' => $enumValues,
            'foreign_table' => $foreignTable,
        ];
    }

    /**
     * Minimal indentation YAML parser for Blueprint drafts (no external deps).
     */
    protected function parseYamlLike(string $input): array
    {
        $lines = preg_split('/\R/', $input) ?: [];
        $root = [];
        $stack = [['indent' => -1, 'ref' => &$root]];

        foreach ($lines as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            if (! preg_match('/^( *)(.*)$/', $line, $m)) {
                continue;
            }

            $indent = strlen($m[1]);
            $content = rtrim($m[2]);

            while (count($stack) > 1 && $indent <= $stack[count($stack) - 1]['indent']) {
                array_pop($stack);
            }

            $parent = &$stack[count($stack) - 1]['ref'];

            // list item: - value
            if (preg_match('/^-\s*(.*)$/', $content, $lm)) {
                $val = $this->scalar(trim($lm[1]));
                if (! is_array($parent)) {
                    $parent = [];
                }
                $parent[] = $val;
                continue;
            }

            // key: value | key:
            if (! preg_match('/^([^:]+):(.*)$/', $content, $km)) {
                // bare flag under model (softDeletes)
                if (is_array($parent)) {
                    $parent[] = $content;
                }
                continue;
            }

            $key = trim($km[1]);
            $rest = trim($km[2]);

            if ($rest === '' || $rest === '|' || $rest === '>') {
                $parent[$key] = [];
                $stack[] = ['indent' => $indent, 'ref' => &$parent[$key]];
            } else {
                $parent[$key] = $this->scalar($rest);
            }
        }

        return $root;
    }

    protected function scalar(string $value): mixed
    {
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if ($value === 'null' || $value === '~') {
            return null;
        }
        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    public function resolveBase(array $controllers, string $modelName, string $fallback = 'user'): string
    {
        $def = $controllers[$modelName] ?? null;
        if (! is_array($def)) {
            return $fallback;
        }

        $resource = strtolower((string) ($def['resource'] ?? ''));

        return match ($resource) {
            'admin' => 'admin',
            'guest' => 'guest',
            'api' => 'user',
            'web' => 'user',
            default => $fallback,
        };
    }

    public function example(): string
    {
        return <<<'YAML'
models:
  Category:
    name: string:120
    slug: string:120 unique

  Product:
    name: string:200
    price: decimal:10,2
    category_id: id foreign
    is_active: boolean default:true

  Order:
    user_id: id foreign
    total: decimal:12,2
    status: enum:pending,paid,cancelled
    softDeletes

controllers:
  Category:
    resource: web
  Product:
    resource: web
  Order:
    resource: admin
YAML;
    }
}
