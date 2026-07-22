<?php

namespace Imran\BlueprintStudio\Support;

class FieldTypes
{
    public static function all(): array
    {
        return config('blueprint-studio.field_types', []);
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $key => $meta) {
            $options[] = [
                'value' => $key,
                'label' => $meta['label'] ?? $key,
                'input' => $meta['input'] ?? 'text',
            ];
        }

        return $options;
    }

    public static function isLocked(string $name): bool
    {
        return in_array($name, ['id', 'timestamps', 'created_at', 'updated_at', 'deleted_at'], true);
    }

    public static function normalizeFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $name = trim((string) ($field['name'] ?? ''));
            $type = (string) ($field['type'] ?? 'string');

            if ($name === '' || self::isLocked($name)) {
                continue;
            }

            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'type' => array_key_exists($type, self::all()) ? $type : 'string',
                'nullable' => (bool) ($field['nullable'] ?? false),
                'unique' => (bool) ($field['unique'] ?? false),
                'default' => $field['default'] ?? null,
                'length' => $field['length'] ?? null,
                'enum_values' => $field['enum_values'] ?? ($field['options'] ?? null),
                'foreign_table' => $field['foreign_table'] ?? null,
                'foreign_column' => $field['foreign_column'] ?? 'id',
                'on_delete' => $field['on_delete'] ?? 'cascade',
                'locked' => false,
            ];
        }

        return $normalized;
    }

    public static function withDefaults(array $fields): array
    {
        $defaults = config('blueprint-studio.default_columns', []);

        return array_merge($defaults, self::normalizeFields($fields));
    }
}
