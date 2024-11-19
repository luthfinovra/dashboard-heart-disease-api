<?php

namespace App\Services;

use App\Models\Disease;
use App\Models\DiseaseRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DiseaseRecordService
{
    protected $diseaseService;
    protected $fileStorage;
    
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    public function __construct(DiseaseService $diseaseService, FileStorageService $fileStorage)
    {
        $this->diseaseService = $diseaseService;
        $this->fileStorage = $fileStorage;
    }

    public function createDiseaseRecord(array $data): array
    {
        try {
            DB::beginTransaction();

            $diseaseId = $data['diseaseId'];
            unset($data['diseaseId']);
            
            foreach ($data['data'] as $key => $value) {
                if (is_array($value) && $this->isFileArray($value)) {
                    $fileUrls = [];
                    foreach ($value as $index => $file) {
                        if ($file && is_file($file)) {
                            $fileUrls[] = $this->fileStorage->storeRecordFile(
                                $file, 
                                $diseaseId, 
                                $key . '-' . ($index + 1)
                            );
                        }
                    }
                    $data['data'][$key] = $fileUrls;
                } elseif (is_file($value)) {
                    $data['data'][$key] = $this->fileStorage->storeRecordFile($value, $diseaseId, $key);
                }
            }

            $diseaseRecord = DiseaseRecord::create([
                'disease_id' => $diseaseId,
                'data' => $data['data'],
            ]);


            DB::commit();
            return [true, 'Disease record created successfully.', $diseaseRecord->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            return [false, 'Disease record creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function editDiseaseRecord($recordId, array $data): array
    {
        try {
            DB::beginTransaction();
            $diseaseRecord = DiseaseRecord::findOrFail($recordId);
            $existingData = $diseaseRecord->data;
            
            foreach ($data['data'] as $key => $value) {
                if (is_array($value) && $this->isFileArray($value)) {
                    $fileUrls = [];
                    
                    // Delete old files
                    if (isset($existingData[$key]) && is_array($existingData[$key])) {
                        foreach ($existingData[$key] as $oldFile) {
                            $this->fileStorage->deleteFile($oldFile);
                        }
                    }
                    
                    // Store new files
                    foreach ($value as $file) {
                        if ($file && is_file($file)) {
                            $fileUrls[] = $this->fileStorage->storeRecordFile(
                                $file,
                                $diseaseRecord->disease_id,
                                $key
                            );
                        }
                    }
                    $existingData[$key] = $fileUrls;
                    
                } elseif (is_file($value)) {
                    // Delete old file
                    if (isset($existingData[$key])) {
                        $this->fileStorage->deleteFile($existingData[$key]);
                    }
                    
                    // Store new file
                    $existingData[$key] = $this->fileStorage->storeRecordFile(
                        $value,
                        $diseaseRecord->disease_id,
                        $key
                    );
                } else {
                    $existingData[$key] = $value;
                }
            }
            
            $diseaseRecord->update([
                'data' => $existingData,
            ]);
            
            DB::commit();
            return [true, 'Disease record updated successfully.', $diseaseRecord->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            return [false, 'Disease record update failed: ' . $exception->getMessage(), []];
        }
    }
    
    public function deleteDiseaseRecord($id): array
    {
        try {
            DB::beginTransaction();
            $diseaseRecord = DiseaseRecord::findOrFail($id);

            foreach ($diseaseRecord->data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $filePath) {
                        $this->fileStorage->deleteFile($filePath);
                    }
                } elseif (is_string($value) && file_exists(storage_path("app/public/{$value}"))) {
                    $this->fileStorage->deleteFile($value);
                }
            }
            
            $diseaseRecord->delete();
            
            DB::commit();
            return [true, 'Disease record deleted successfully.', []];
        } catch (\Throwable $exception) {
            DB::rollBack();
            return [false, 'Disease record deletion failed: ' . $exception->getMessage(), []];
        }
    }

    public function getDiseaseRecords($diseaseId, array $filters)
    {
        try {
            $schema = $this->diseaseService->getSchemaField($diseaseId);

            $disease = Disease::findOrFail($diseaseId);
            
            if (empty($schema)) {
                return [false, 'Schema not found for this disease.', []];
            }

            $query = $this->buildDiseaseRecordQuery($diseaseId, $filters);
            $paginatedData = $this->paginateResults($query, $filters);

            $response = [
                'name' => $disease['name'],
                'deskripsi' => $disease['deskripsi'],
                'schema' => $schema,
                'records' => $paginatedData['records'],
                'pagination' => $paginatedData['pagination']
            ];

            return [true, 'Disease records retrieved successfully.', $response];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve disease records: ' . $exception->getMessage(), []];
        }
    }

    public function getDiseaseRecordDetails($diseaseId, $recordId): array
    {
        try {
            $schema = $this->diseaseService->getSchemaField($diseaseId);

            if (empty($schema)) {
                return [false, 'Schema not found for this disease.', []];
            }

            $record = DiseaseRecord::where('disease_id', $diseaseId)
                ->where('id', $recordId)
                ->firstOrFail();

            $response = [
                'schema' => $schema,
                'record' => $record,
            ];

            return [true, 'Disease record details retrieved successfully.', $response];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve disease record details: ' . $exception->getMessage(), []];
        }
    }

    private function buildDiseaseRecordQuery(int $diseaseId, array $filters): Builder
    {
        $query = DiseaseRecord::where('disease_id', $diseaseId);
        
        // $this->applyDiseaseRecordFilters($query, $filters);
        
        return $query;
    }

    private function paginateResults(Builder $query, array $filters): array
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
            'records' => $results->items(),
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

    private function isFileArray(array $value): bool
    {
        return count(array_filter($value, function ($item) {
            return is_object($item) && method_exists($item, 'getClientOriginalExtension');
        })) > 0;
    }
}
