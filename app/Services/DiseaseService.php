<?php

namespace App\Services;

use App\Models\Disease;
use App\Http\Requests\CreateDiseaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiseaseService
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    protected $fileStorage;
    
    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }


    public function createDisease(array $data): array
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
            return [true, 'Disease created successfully.', $disease->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            // TO DO logging
            return [false, 'Disease creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function editDisease($id, array $data): array
    {
        try {
            // var_dump($data);
            DB::beginTransaction();

            $disease = Disease::find($id);
            if (!$disease) {
                return [false, 'Disease not found.', []];
            }

            if (isset($data['cover_page']) && $data['cover_page']) {
                if ($disease->cover_page) {
                    $this->fileStorage->deleteFile($disease->cover_page, true);
                }

                $data['cover_page'] = $this->fileStorage->storeCoverImage($data['cover_page'], $disease->id);
            } else {
                unset($data['cover_page']);
            }

            $disease->update($data);
            DB::commit();
            return [true, 'Disease updated successfully.', $disease];
        } catch (\Throwable $exception) {
            DB::rollBack();
            return [false, 'Disease update failed: ' . $exception->getMessage(), []];
        }
    }


    public function deleteDisease($id): array
    {
        try {
            DB::beginTransaction();
            $disease = Disease::findOrFail($id);

            if ($disease->cover_page) {
                $this->fileStorage->deleteFile($disease->cover_page, true);
            }

            $disease->delete();

            DB::commit();
            return [true, 'Disease deleted successfully.', []];
        } catch (\Throwable $exception) {
            DB::rollBack();
            // TO DO logging
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
            $query->where('name', 'like', '%' . trim($filters['name']) . '%');
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
