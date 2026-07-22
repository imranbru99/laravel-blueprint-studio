<?php

namespace Imran\BlueprintStudio\Support;

use Illuminate\Support\Str;

class Naming
{
    public static function model(string $name): string
    {
        return Str::studly(Str::singular($name));
    }

    public static function table(string $model): string
    {
        return Str::snake(Str::plural(self::model($model)));
    }

    public static function variable(string $model): string
    {
        return Str::camel(self::model($model));
    }

    public static function variables(string $model): string
    {
        return Str::camel(Str::plural(self::model($model)));
    }

    public static function controller(string $model): string
    {
        return self::model($model).'Controller';
    }

    public static function request(string $model, string $action = 'Store'): string
    {
        return $action.self::model($model).'Request';
    }

    public static function route(string $model): string
    {
        return Str::kebab(Str::plural(self::model($model)));
    }

    public static function viewFolder(string $model): string
    {
        return Str::kebab(Str::plural(self::model($model)));
    }

    public static function migrationFile(string $model): string
    {
        $table = self::table($model);
        $timestamp = now()->format('Y_m_d_His');

        return "{$timestamp}_create_{$table}_table.php";
    }

    public static function label(string $field): string
    {
        return Str::title(str_replace('_', ' ', $field));
    }
}
