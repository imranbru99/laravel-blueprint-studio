<?php

namespace Imran\BlueprintStudio\Services;

use Imran\BlueprintStudio\Models\BlueprintHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HistoryService
{
    public function record(string $action, string $resource, array $payload = [], array $files = [], string $status = 'success', ?string $message = null): BlueprintHistory
    {
        return BlueprintHistory::query()->create([
            'action' => $action,
            'resource' => $resource,
            'payload' => $payload,
            'files' => $files,
            'status' => $status,
            'message' => $message,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function latest(int $limit = 50): Collection
    {
        return BlueprintHistory::query()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return BlueprintHistory::query()->latest()->paginate($perPage);
    }

    public function clear(): int
    {
        return BlueprintHistory::query()->delete();
    }

    public function stats(): array
    {
        return [
            'total' => BlueprintHistory::query()->count(),
            'models' => BlueprintHistory::query()->whereIn('action', ['model.create', 'model.update'])->count(),
            'controllers' => BlueprintHistory::query()->where('action', 'controller.create')->count(),
            'full_crud' => BlueprintHistory::query()->where('action', 'crud.generate')->count(),
            'failures' => BlueprintHistory::query()->where('status', 'failed')->count(),
        ];
    }
}
