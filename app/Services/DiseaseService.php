<?php

namespace App\Services;

use App\Models\Disease;
use App\Http\Requests\CreateDiseaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\LogActionService;

class DiseaseService
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    protected $fileStorage;
    
    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }


    public function createDisease(array $data, $userId): array
    {
        try {
            DB::beginTransaction();
            
            $image_url = null;
            if (isset($data['cover_page']) && $data['cover_page']) {
                $disease = Disease::create([
                    'name' => $data['name'],
                    'deskripsi' => $data['deskripsi'],
                    'schema' => $data['schema'],
                    'cover_page' => null,
                ]);

                $image_url = $this->fileStorage->storeCoverImage($data['cover_page'], $disease->id);
                
                $disease->update(['cover_page' => $image_url]);
            } else {
                $disease = Disease::create([
                    'name' => $data['name'],
                    'deskripsi' => $data['deskripsi'],
                    'schema' => $data['schema'],
                    'cover_page' => null,
                ]);
            }

            DB::commit();

            LogActionService::logAction(
                $userId,
                'create',
                'Disease',
                $disease->id,
                $disease->toArray(),
                "Created disease: {$disease->name}",
                true
            );

            return [true, 'Disease created successfully.', $disease->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            
            LogActionService::logAction(
                $userId,
                'create',
                'Disease',
                null,
                [
                    'name' => $data['name'] ?? null,
                    'error' => $exception->getMessage()
                ],
                "Failed to create disease: {$exception->getMessage()}",
                false
            );

            return [false, 'Disease creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function editDisease($id, array $data, $userId): array
    {
        try {
            // var_dump($data);
            DB::beginTransaction();

            $disease = Disease::find($id);
            if (!$disease) {
                DB::rollBack();
                LogActionService::logAction(
                    $userId,
                    'edit',
                    'Disease',
                    $id,
                    ['error' => 'Disease not found'],
                    "Failed to edit disease: Disease not found",
                    false
                );
                return [false, 'Disease not found.', []];
            }

            $oldData = $disease->toArray();

            if (isset($data['cover_page']) && $data['cover_page']) {
                if ($disease->cover_page) {
                    $this->fileStorage->deleteFile($disease->cover_page, true);
                }

                $data['cover_page'] = $this->fileStorage->storeCoverImage($data['cover_page'], $disease->id);
            } else {
                unset($data['cover_page']);
            }

            $changedFields = array_keys(array_diff_assoc($data, $oldData));
            
            // Get only the values that are actually changing
            $relevantOldData = array_intersect_key($oldData, $data);
            $relevantNewData = array_intersect_key($data, $oldData);

            $disease->update($data);
            DB::commit();

            LogActionService::logAction(
                $userId,
                'edit',
                'Disease',
                $disease->id,
                [
                    'old' => $relevantOldData,
                    'new' => $relevantNewData,
                    'changed_fields' => $changedFields
                ],
                "Updated disease: {$disease->name}",
                true
            );

            return [true, 'Disease updated successfully.', $disease];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                $userId,
                'edit',
                'Disease',
                $id,
                [
                    'attempted_changes' => array_keys($data),
                    'error' => $exception->getMessage()
                ],
                "Failed to update disease: {$exception->getMessage()}",
                false
            );
            return [false, 'Disease update failed: ' . $exception->getMessage(), []];
        }
    }


    public function deleteDisease($id, $userId): array
    {
        try {
            DB::beginTransaction();
            $disease = Disease::find($id);
            if (!$disease) {
                DB::rollBack();
                LogActionService::logAction(
                    $userId,
                    'delete',
                    'Disease',
                    $id,
                    ['error' => 'Disease not found'],
                    "Failed to delete disease: Disease not found",
                    false
                );
                return [false, 'Disease not found.', []];
            }

            $diseaseInfo = [
                'name' => $disease->name,
                'had_cover_page' => !is_null($disease->cover_page),
                'schema_columns_count' => count($disease->schema['columns'] ?? [])
            ];

            if ($disease->cover_page) {
                $this->fileStorage->deleteFile($disease->cover_page, true);
            }

            $disease->delete();

            DB::commit();
            LogActionService::logAction(
                $userId,
                'delete',
                'Disease',
                $id,
                $diseaseInfo,
                "Deleted disease: {$disease->name}",
                true
            );
            return [true, 'Disease deleted successfully.', []];
        } catch (\Throwable $exception) {
            DB::rollBack();
            
            LogActionService::logAction(
                $userId,
                'delete',
                'Disease',
                $id,
                ['error' => $exception->getMessage()],
                "Failed to delete disease: {$exception->getMessage()}",
                false
            );
            return [false, 'Disease deletion failed: ' . $exception->getMessage(), []];
        }
    }
    
    public function getDisease(array $filters): array
    {
        try {
            $query = Disease::query();
            
            $query->withCount('diseaseRecords');
            
            $this->applyDiseaseFilters($query, $filters);
            
            $paginatedData = $this->paginateResults($query, $filters, 'diseases');
            
            return [true, 'Diseases retrieved successfully.', $paginatedData];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve diseases: ' . $exception->getMessage(), []];
        }
    }
    
    public function getDiseaseDetails($id): array
    {
        try {
            $disease = Disease::withCount('diseaseRecords')->findOrFail($id);
            return [true, 'Disease details retrieved successfully.', $disease];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve disease details: ' . $exception->getMessage(), []];
        }
    }
    
    public function getStatistics(array $filters): array
    {
        try {
            $query = Disease::query();
            $query->withCount('diseaseRecords');

            $this->applyDiseaseFilters($query, $filters);

            $diseases = $query->get(['id', 'name', 'disease_records_count']);

            $totalDiseaseRecords = $diseases->sum('disease_records_count');

            $stats = [
                'total_disease_records' => $totalDiseaseRecords,
                'diseases' => $diseases->map(function ($disease) {
                    return [
                        'id' => $disease->id,
                        'name' => $disease->name,
                        'disease_records_count' => $disease->disease_records_count,
                    ];
                }),
            ];
    
            return [true, 'Disease statistics retrieved successfully.', $stats];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve statistics: ' . $exception->getMessage(), []];
        }
    }
    

    public function getSchemaField(int $diseaseId): array
    {
        try {
            [$success, $message, $disease] = $this->getDiseaseDetails($diseaseId);
            
            if (!$success) {
                return [];
            }
            
            $schema = $disease->schema['columns'] ?? null;
            
            if (!$schema) {
                return [];
            }
            
            $formattedSchema = [];
            foreach ($schema as $column) {
                $field = [
                    'name' => $column['name'] ?? null,
                    'type' => $column['type'] ?? null,
                ];
                
                if ($field['type'] === 'file') {
                    $field['format'] = $column['format'];

                    if (isset($column['multiple']) && $column['multiple']) {
                        $field['multiple'] = true;
                    }
                } else {
                    $field['is_visible'] = filter_var($column['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                }
                
                $formattedSchema[] = $field;
            }

            return $formattedSchema;
        } catch (\Throwable $exception) {
            return []; 
        }
    }
    
    private function applyDiseaseFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . trim($filters['name']) . '%');
        }
        
        if (!empty($filters['column_type'])) {
            $query->whereJsonContains('schema->columns', ['type' => $filters['column_type']]);
        }
        
        if (!empty($filters['column_name'])) {
            $query->whereJsonContains('schema->columns', ['name' => $filters['column_name']]);
        }
        
        //Sorting
        $query->orderBy('created_at', 'desc');
    }
    
    private function paginateResults(Builder $query, array $filters, string $itemsKey): array
    {
        $perPage = isset($filters['per_page']) && 
                  is_numeric($filters['per_page']) && 
                  $filters['per_page'] > 0 && 
                  $filters['per_page'] <= self::MAX_PER_PAGE
            ? (int) $filters['per_page']
            : self::DEFAULT_PER_PAGE;

        $page = isset($filters['page']) && 
                is_numeric($filters['page']) && 
                $filters['page'] > 0
            ? (int) $filters['page']
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
                'per_page' => (int) $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
                'has_more_pages' => $results->hasMorePages(),
            ]
        ];
    }
}
