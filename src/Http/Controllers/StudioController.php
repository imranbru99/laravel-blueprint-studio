<?php

namespace Imran\BlueprintStudio\Http\Controllers;

use Imran\BlueprintStudio\Services\BlueprintOrchestrator;
use Imran\BlueprintStudio\Services\HistoryService;
use Imran\BlueprintStudio\Support\FieldTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;

class StudioController extends Controller
{
    public function __construct(
        protected BlueprintOrchestrator $studio,
        protected HistoryService $history,
    ) {}

    public function index(): View
    {
        $project = $this->studio->inspectProject();

        return view('blueprint-studio::studio.index', [
            'project' => $project,
            'brand' => $project['brand'],
        ]);
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->studio->inspectProject(),
            'history' => $this->history->latest(30),
        ]);
    }

    public function createModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:64'],
            'fields.*.type' => ['required_with:fields', 'string'],
            'fields.*.nullable' => ['sometimes', 'boolean'],
            'fields.*.unique' => ['sometimes', 'boolean'],
            'fields.*.default' => ['nullable'],
            'fields.*.enum_values' => ['nullable'],
            'fields.*.foreign_table' => ['nullable', 'string'],
            'soft_deletes' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->studio->createModelBundle(
                $validated['name'],
                $validated['fields'] ?? [],
                [
                    'soft_deletes' => $validated['soft_deletes'] ?? false,
                    'update_migration' => true,
                ]
            );

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function syncFields(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:64'],
            'fields.*.type' => ['required_with:fields', 'string'],
            'fields.*.nullable' => ['sometimes', 'boolean'],
            'fields.*.unique' => ['sometimes', 'boolean'],
            'fields.*.default' => ['nullable'],
            'fields.*.enum_values' => ['nullable'],
            'fields.*.foreign_table' => ['nullable', 'string'],
            'soft_deletes' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->studio->syncFields(
                $validated['name'],
                $validated['fields'] ?? [],
                ['soft_deletes' => $validated['soft_deletes'] ?? false]
            );

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function createController(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'base' => ['required', 'in:user,admin,guest'],
            'fields' => ['nullable', 'array'],
            'with_views' => ['sometimes', 'boolean'],
            'with_requests' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->studio->generateController(
                $validated['name'],
                $validated['fields'] ?? [],
                $validated['base'],
                [
                    'with_views' => $validated['with_views'] ?? true,
                    'with_requests' => $validated['with_requests'] ?? true,
                ]
            );

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function generateCrud(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'base' => ['required', 'in:user,admin,guest'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:64'],
            'fields.*.type' => ['required_with:fields', 'string'],
            'fields.*.nullable' => ['sometimes', 'boolean'],
            'fields.*.unique' => ['sometimes', 'boolean'],
            'fields.*.default' => ['nullable'],
            'fields.*.enum_values' => ['nullable'],
            'fields.*.foreign_table' => ['nullable', 'string'],
            'soft_deletes' => ['sometimes', 'boolean'],
            'components' => ['sometimes', 'array'],
            'components.model' => ['sometimes', 'boolean'],
            'components.migration' => ['sometimes', 'boolean'],
            'components.controller' => ['sometimes', 'boolean'],
            'components.route' => ['sometimes', 'boolean'],
            'components.view' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->studio->generateCrud(
                $validated['name'],
                $validated['fields'] ?? [],
                $validated['base'],
                [
                    'soft_deletes' => $validated['soft_deletes'] ?? false,
                    'components' => $validated['components'] ?? null,
                ]
            );

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function generateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base' => ['sometimes', 'in:user,admin,guest'],
            'soft_deletes' => ['sometimes', 'boolean'],
            'components' => ['sometimes', 'array'],
            'components.model' => ['sometimes', 'boolean'],
            'components.migration' => ['sometimes', 'boolean'],
            'components.controller' => ['sometimes', 'boolean'],
            'components.route' => ['sometimes', 'boolean'],
            'components.view' => ['sometimes', 'boolean'],
            'models' => ['required', 'array', 'min:1'],
            'models.*.name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'models.*.fields' => ['nullable', 'array'],
            'models.*.fields.*.name' => ['required_with:models.*.fields', 'string', 'max:64'],
            'models.*.fields.*.type' => ['required_with:models.*.fields', 'string'],
            'models.*.fields.*.nullable' => ['sometimes', 'boolean'],
            'models.*.fields.*.unique' => ['sometimes', 'boolean'],
            'models.*.fields.*.default' => ['nullable'],
            'models.*.fields.*.enum_values' => ['nullable'],
            'models.*.fields.*.foreign_table' => ['nullable', 'string'],
            'models.*.soft_deletes' => ['sometimes', 'boolean'],
            'models.*.base' => ['sometimes', 'in:user,admin,guest'],
        ]);

        try {
            $result = $this->studio->generateBatch(
                $validated['models'],
                $validated['base'] ?? 'user',
                [
                    'soft_deletes' => $validated['soft_deletes'] ?? false,
                    'components' => $validated['components'] ?? null,
                ]
            );

            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function history(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->history->latest(100),
            'stats' => $this->history->stats(),
        ]);
    }

    public function clearHistory(): JsonResponse
    {
        $deleted = $this->history->clear();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => 'History cleared.',
        ]);
    }

    public function fieldTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => FieldTypes::options(),
            'defaults' => config('blueprint-studio.default_columns'),
        ]);
    }

    public function parseDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft' => ['required', 'string', 'min:3'],
        ]);

        try {
            $parsed = $this->studio->parseDraft($validated['draft']);

            return response()->json([
                'success' => true,
                'data' => $parsed,
                'message' => count($parsed['models']).' model(s) parsed.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function importDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft' => ['required', 'string', 'min:3'],
            'base' => ['sometimes', 'in:user,admin,guest'],
            'soft_deletes' => ['sometimes', 'boolean'],
            'components' => ['sometimes', 'array'],
            'components.model' => ['sometimes', 'boolean'],
            'components.migration' => ['sometimes', 'boolean'],
            'components.controller' => ['sometimes', 'boolean'],
            'components.route' => ['sometimes', 'boolean'],
            'components.view' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->studio->generateFromDraft(
                $validated['draft'],
                $validated['base'] ?? 'user',
                [
                    'soft_deletes' => $validated['soft_deletes'] ?? false,
                    'components' => $validated['components'] ?? null,
                ]
            );

            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
