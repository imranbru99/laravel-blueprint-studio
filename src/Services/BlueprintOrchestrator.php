<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Support\FieldTypes;
use Imran\BlueprintStudio\Support\Naming;
use Imran\BlueprintStudio\Support\BlueprintDraftParser;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Throwable;

class BlueprintOrchestrator
{
    public function __construct(
        protected ModelGenerator $models,
        protected MigrationGenerator $migrations,
        protected ControllerGenerator $controllers,
        protected ViewGenerator $views,
        protected RequestGenerator $requests,
        protected LayoutManager $layouts,
        protected HistoryService $history,
        protected RouteRegistrar $routes,
    ) {}

    /**
     * @return array{model: bool, migration: bool, controller: bool, route: bool, view: bool}
     */
    public function normalizeComponents(?array $components): array
    {
        $defaults = [
            'model' => true,
            'migration' => true,
            'controller' => true,
            'route' => true,
            'view' => true,
        ];

        if ($components === null || $components === []) {
            return $defaults;
        }

        $out = [];
        foreach ($defaults as $key => $default) {
            $out[$key] = array_key_exists($key, $components)
                ? filter_var($components[$key], FILTER_VALIDATE_BOOLEAN)
                : $default;
        }

        if (! in_array(true, $out, true)) {
            throw new InvalidArgumentException('Select at least one component: model, migration, controller, route, or view.');
        }

        return $out;
    }

    public function createModelBundle(string $name, array $fields = [], array $options = []): array
    {
        $model = Naming::model($name);
        $fields = FieldTypes::normalizeFields($fields);
        $visualFields = FieldTypes::withDefaults($fields);
        $silent = (bool) ($options['silent'] ?? false);
        $components = $this->normalizeComponents($options['components'] ?? [
            'model' => true,
            'migration' => true,
            'controller' => false,
            'route' => false,
            'view' => false,
        ]);

        $modelExisted = $this->models->exists($model);
        $migrationExisted = (bool) $this->migrations->findCreateMigration(Naming::table($model));

        try {
            $migration = null;
            $modelResult = null;

            if ($components['migration']) {
                $migration = $migrationExisted
                    ? $this->migrations->update($model, $visualFields, $options)
                    : $this->migrations->create($model, $visualFields, $options);
            }

            if ($components['model']) {
                $modelResult = $modelExisted
                    ? $this->models->updateFillable($model, $fields, $options)
                    : $this->models->create($model, $fields, $options);
            }

            $updated = ($components['model'] && $modelExisted) || ($components['migration'] && $migrationExisted);
            $action = $updated ? 'model.update' : 'model.create';
            $parts = array_keys(array_filter([
                'model' => $components['model'],
                'migration' => $components['migration'],
            ]));
            $message = $updated
                ? 'Updated '.implode(' + ', $parts)." for {$model}."
                : 'Created '.implode(' + ', $parts)." for {$model}.";

            $files = array_values(array_filter([
                $modelResult['relative'] ?? null,
                $migration['relative'] ?? null,
            ]));

            if (! $silent) {
                $this->history->record($action, $model, [
                    'fields' => $visualFields,
                    'options' => $options,
                    'components' => $components,
                    'updated' => $updated,
                ], $files, 'success', $message);
            }

            return [
                'success' => true,
                'updated' => $updated,
                'model' => $modelResult,
                'migration' => $migration,
                'fields' => $visualFields,
                'components' => $components,
                'message' => $message,
            ];
        } catch (Throwable $e) {
            if (! $silent) {
                $this->history->record(
                    $modelExisted ? 'model.update' : 'model.create',
                    $model,
                    ['fields' => $visualFields, 'components' => $components],
                    [],
                    'failed',
                    $e->getMessage()
                );
            }

            throw $e;
        }
    }

    public function syncFields(string $name, array $fields = [], array $options = []): array
    {
        $model = Naming::model($name);
        $fields = FieldTypes::normalizeFields($fields);
        $visualFields = FieldTypes::withDefaults($fields);
        $modelExisted = $this->models->exists($model);

        $migration = $this->migrations->update($model, $visualFields, $options);
        $modelResult = $modelExisted
            ? $this->models->updateFillable($model, $fields, $options)
            : $this->models->create($model, $fields, $options);

        $this->history->record('fields.sync', $model, [
            'fields' => $visualFields,
        ], array_values(array_filter([
            $modelResult['relative'] ?? null,
            $migration['relative'] ?? null,
        ])), 'success', "Fields synced for {$model}.");

        return [
            'success' => true,
            'updated' => $modelExisted,
            'model' => $modelResult,
            'migration' => $migration,
            'fields' => $visualFields,
            'message' => 'Migration columns and model fillable updated.',
        ];
    }

    public function generateCrud(string $name, array $fields = [], string $base = 'user', array $options = []): array
    {
        $model = Naming::model($name);
        $fields = FieldTypes::normalizeFields($fields);
        $visualFields = FieldTypes::withDefaults($fields);
        $base = $this->normalizeBase($base);
        $components = $this->normalizeComponents($options['components'] ?? null);

        try {
            $layout = null;
            if ($components['view'] || $components['controller']) {
                $layout = $this->layouts->ensure();
            }

            $bundle = [
                'updated' => false,
                'model' => null,
                'migration' => null,
            ];

            if ($components['model'] || $components['migration']) {
                $bundle = $this->createModelBundle($model, $fields, array_merge($options, [
                    'silent' => true,
                    'components' => [
                        'model' => $components['model'],
                        'migration' => $components['migration'],
                        'controller' => false,
                        'route' => false,
                        'view' => false,
                    ],
                ]));
            }

            $requestFiles = [];
            $controller = null;
            if ($components['controller']) {
                $requestFiles = $this->requests->create($model, $fields, $base);
                $controller = $this->controllers->create($model, $fields, $base, [
                    'with_requests' => true,
                ]);
            }

            $viewFiles = [];
            if ($components['view']) {
                if ($layout === null) {
                    $layout = $this->layouts->ensure();
                }
                $viewFiles = $this->views->create($model, $fields, $base);
            }

            $routeResult = null;
            if ($components['route']) {
                $routeResult = $this->routes->register($model, $base);
            }

            $allFiles = array_merge(
                array_filter([
                    $bundle['model']['relative'] ?? null,
                    $bundle['migration']['relative'] ?? null,
                    $controller['relative'] ?? null,
                    ($layout['created'] ?? false) ? ($layout['relative'] ?? null) : null,
                    ($routeResult['updated'] ?? false) ? ($routeResult['relative'] ?? null) : null,
                ]),
                array_column($requestFiles, 'relative'),
                array_column($viewFiles, 'relative'),
            );

            $enabled = array_keys(array_filter($components));
            $this->history->record('crud.generate', $model, [
                'fields' => $visualFields,
                'base' => $base,
                'options' => $options,
                'components' => $components,
                'route' => $routeResult,
                'route_hint' => $routeResult['hint'] ?? $this->routes->hint($model, $base),
            ], $allFiles, 'success', "Generated [".implode(', ', $enabled)."] for {$model} ({$base}).");

            return [
                'success' => true,
                'updated' => $bundle['updated'] ?? false,
                'model' => $bundle['model'],
                'migration' => $bundle['migration'],
                'controller' => $controller,
                'requests' => $requestFiles,
                'views' => $viewFiles,
                'layout' => $layout,
                'route' => $routeResult,
                'fields' => $visualFields,
                'components' => $components,
                'route_hint' => $routeResult['hint'] ?? $this->routes->hint($model, $base),
                'message' => "Scaffolded {$model}: ".implode(', ', $enabled).'.',
            ];
        } catch (Throwable $e) {
            $this->history->record('crud.generate', $model, [
                'base' => $base,
                'fields' => $visualFields,
                'components' => $components,
            ], [], 'failed', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Generate multiple models in one request.
     *
     * @param  array<int, array{name: string, fields?: array, soft_deletes?: bool, base?: string}>  $models
     */
    public function generateBatch(array $models, string $defaultBase = 'user', array $options = []): array
    {
        $defaultBase = $this->normalizeBase($defaultBase);
        $components = $this->normalizeComponents($options['components'] ?? null);
        $results = [];
        $errors = [];

        foreach ($models as $def) {
            $name = trim((string) ($def['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $base = $this->normalizeBase((string) ($def['base'] ?? $defaultBase));

            try {
                $results[] = $this->generateCrud(
                    $name,
                    $def['fields'] ?? [],
                    $base,
                    array_merge($options, [
                        'soft_deletes' => (bool) ($def['soft_deletes'] ?? $options['soft_deletes'] ?? false),
                        'components' => $components,
                    ])
                );
            } catch (Throwable $e) {
                $errors[] = [
                    'model' => Naming::model($name),
                    'message' => $e->getMessage(),
                ];
            }
        }

        if (empty($results) && empty($errors)) {
            throw new InvalidArgumentException('No models provided to generate.');
        }

        $fileList = [];
        foreach ($results as $r) {
            foreach ([
                $r['model']['relative'] ?? null,
                $r['migration']['relative'] ?? null,
                $r['controller']['relative'] ?? null,
            ] as $path) {
                if ($path) {
                    $fileList[] = $path;
                }
            }
            foreach ($r['views'] ?? [] as $v) {
                if (! empty($v['relative'])) {
                    $fileList[] = $v['relative'];
                }
            }
            if (! empty($r['route']['updated']) && ! empty($r['route']['relative'])) {
                $fileList[] = $r['route']['relative'];
            }
        }

        $names = array_map(fn ($r) => Naming::model(
            is_array($r['model'] ?? null)
                ? (class_basename($r['model']['class'] ?? '') ?: 'Model')
                : ($r['fields'][0]['name'] ?? 'Model')
        ), $results);

        // Prefer resource names from successful payloads
        $resourceNames = [];
        foreach ($results as $r) {
            if (! empty($r['model']['class'])) {
                $resourceNames[] = class_basename($r['model']['class']);
            } elseif (! empty($r['migration']['table'])) {
                $resourceNames[] = Naming::model($r['migration']['table']);
            }
        }

        $this->history->record('crud.batch', 'batch', [
            'models' => $resourceNames ?: $names,
            'base' => $defaultBase,
            'components' => $components,
            'errors' => $errors,
        ], array_values(array_unique($fileList)), empty($errors) ? 'success' : 'failed',
            count($results).' model(s) generated'.(count($errors) ? ', '.count($errors).' failed' : '').'.'
        );

        return [
            'success' => empty($errors),
            'results' => $results,
            'errors' => $errors,
            'components' => $components,
            'message' => empty($errors)
                ? count($results).' model(s) generated.'
                : count($results).' generated, '.count($errors).' failed.',
        ];
    }

    public function generateController(string $name, array $fields = [], string $base = 'user', array $options = []): array
    {
        $model = Naming::model($name);
        $fields = FieldTypes::normalizeFields($fields);
        $base = $this->normalizeBase($base);
        $components = $this->normalizeComponents($options['components'] ?? [
            'model' => false,
            'migration' => false,
            'controller' => true,
            'route' => true,
            'view' => (bool) ($options['with_views'] ?? true),
        ]);

        return $this->generateCrud($model, $fields, $base, array_merge($options, [
            'components' => $components,
        ]));
    }

    public function inspectProject(): array
    {
        $modelsPath = config('blueprint-studio.paths.models');
        $models = [];

        if (File::isDirectory($modelsPath)) {
            foreach (File::files($modelsPath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $models[] = $file->getFilenameWithoutExtension();
            }
            $models = array_values(array_unique($models));
            sort($models);
        }

        $parser = new BlueprintDraftParser;

        return [
            'models' => $models,
            'layout' => $this->layouts->status(),
            'controller_bases' => config('blueprint-studio.controller_bases'),
            'field_types' => FieldTypes::options(),
            'default_columns' => config('blueprint-studio.default_columns'),
            'stats' => $this->history->stats(),
            'brand' => config('blueprint-studio.brand'),
            'auto_routes' => (bool) config('blueprint-studio.auto_routes', true),
            'draft_example' => $parser->example(),
            'default_components' => [
                'model' => true,
                'migration' => true,
                'controller' => true,
                'route' => true,
                'view' => true,
            ],
        ];
    }

    public function parseDraft(string $draft): array
    {
        $parser = new BlueprintDraftParser;
        $parsed = $parser->parse($draft);

        if (empty($parsed['models'])) {
            throw new InvalidArgumentException('No models found in draft. Use Laravel Blueprint YAML format under `models:`.');
        }

        return $parsed;
    }

    public function generateFromDraft(string $draft, string $defaultBase = 'user', array $options = []): array
    {
        $parser = new BlueprintDraftParser;
        $parsed = $this->parseDraft($draft);
        $defaultBase = $this->normalizeBase($defaultBase);
        $components = $this->normalizeComponents($options['components'] ?? null);

        $batchModels = [];
        foreach ($parsed['models'] as $modelDef) {
            $batchModels[] = [
                'name' => $modelDef['name'],
                'fields' => $modelDef['fields'] ?? [],
                'soft_deletes' => (bool) ($modelDef['soft_deletes'] ?? false),
                'base' => $parser->resolveBase($parsed['controllers'], $modelDef['name'], $defaultBase),
            ];
        }

        $batch = $this->generateBatch($batchModels, $defaultBase, array_merge($options, [
            'components' => $components,
        ]));

        return array_merge($batch, [
            'parsed' => $parsed,
            'message' => empty($batch['errors'])
                ? count($batch['results']).' model(s) generated from draft.'
                : count($batch['results']).' generated, '.count($batch['errors']).' failed.',
        ]);
    }

    protected function normalizeBase(string $base): string
    {
        $allowed = array_keys(config('blueprint-studio.controller_bases', ['user' => []]));

        return in_array($base, $allowed, true) ? $base : 'user';
    }
}
