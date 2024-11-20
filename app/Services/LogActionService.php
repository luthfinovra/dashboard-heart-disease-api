<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Database\Eloquent\Builder;

class LogActionService{

    const MAX_PER_PAGE = 50;
    const DEFAULT_PER_PAGE = 10;

    public static function logAction(int $userId,
    string $action,
    ?string $model = null,
    ?int $modelId = null,
    ?array $changes = null,
    ?string $description = null,
    $success)
    {
        try {
            Log::create([
                'user_id' => $userId,
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'changes' => $changes ? json_encode($changes) : null,
                'description' => $description,
                'timestamp' => now(),
                'is_success' => $success,
            ]);
        } catch (\Exception $e) {
            LogFacade::info('Error inserting log: ' . $e->getMessage());
            throw $e; // or handle it gracefully
        }
    }

    public function getLogs(array $filters = []): array
    {
        try {
            $query = Log::query();
            $query->with(['user:id,name,role']); 

            $this->applyLogFilters($query, $filters);

            $paginatedData = $this->paginateResults($query, $filters, 'logs');

            return [true, 'Logs retrieved successfully.', $paginatedData];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve logs: ' . $exception->getMessage(), []];
        }
    }

    private function applyLogFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['model'])) {
            $query->where('model', $filters['model']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('timestamp', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('timestamp', '<=', $filters['date_to']);
        }

        if (isset($filters['is_success'])) {
            
            $query->where('is_success', $filters['is_success']);
            // $successFilter = filter_var($filters['is_success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            // if (!is_null($successFilter)) {
            //     $query->where('is_success', $successFilter);
            // }
        }

        // Default sorting by timestamp
        $query->orderBy('timestamp', 'desc');
    }

    private function paginateResults(Builder $query, array $filters, string $itemsKey): array
    {
        $perPage = isset($filters['per_page']) &&
                   is_numeric($filters['per_page']) &&
                   $filters['per_page'] > 0 &&
                   $filters['per_page'] <= self::MAX_PER_PAGE
            ? (int)$filters['per_page']
            : self::DEFAULT_PER_PAGE;

        $page = isset($filters['page']) &&
                is_numeric($filters['page']) &&
                $filters['page'] > 0
            ? (int)$filters['page']
            : 1;

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        if ($results->isEmpty() && $results->currentPage() > 1) {
            $results = $query->paginate($perPage, ['*'], 'page', $results->lastPage());
        }

        return [
            $itemsKey => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => (int)$results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
                'has_more_pages' => $results->hasMorePages(),
            ]
        ];
    }

}
