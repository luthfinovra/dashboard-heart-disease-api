<?php

namespace App\Services;

use App\Models\Disease;
use App\Models\DiseaseRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\LogActionService;

class DiseaseRecordService
{
    protected $diseaseService;
    protected $fileStorage;
    
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    public function __construct(
        DiseaseService $diseaseService, 
        FileStorageService $fileStorage)
    {
        $this->diseaseService = $diseaseService;
        $this->fileStorage = $fileStorage;
    }

    public function createDiseaseRecord(array $data, $userId): array
    {
        try {
            DB::beginTransaction();

            $diseaseId = $data['diseaseId'];
            unset($data['diseaseId']);
            
            [$schema, $message] = $this->diseaseService->getSchemaField($diseaseId);

            foreach ($data['data'] as $key => $value) {

                $fieldSchema = collect($schema)->firstWhere('name', $key);

                if (!$fieldSchema) {
                    throw new \Exception("Field '{$key}' is not defined in the schema.");
                }

                if ($fieldSchema['type'] === 'file') {
                    $isMultiple = $fieldSchema['multiple'] ?? false;

                    if ($isMultiple) {
                        $value = is_array($value) ? $value : [$value];
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
                    } else if (is_file($value)){
                        $data['data'][$key] = $this->fileStorage->storeRecordFile($value, $diseaseId, $key);
                    }
                } else {
                    // Process non-file fields normally
                    $data['data'][$key] = $value;
                }
            }

            $diseaseRecord = DiseaseRecord::create([
                'disease_id' => $diseaseId,
                'data' => $data['data'],
            ]);


            DB::commit();

            LogActionService::logAction(
                $userId,
                'create',
                'DiseaseRecord',
                $diseaseRecord->id,
                $diseaseRecord->toArray(),
                "Created disease record for disease ID: {$diseaseId}",
                true
            );
            return [true, 'Disease record created successfully.', $diseaseRecord->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                $userId,
                'create',
                'DiseaseRecord',
                null,
                [
                    'disease_id' => $diseaseId ?? null,
                    'error' => $exception->getMessage()
                ],
                "Failed to create disease record: {$exception->getMessage()}",
                false
            );
            return [false, 'Disease record creation failed: ' . $exception->getMessage(), []];
        }

    }

    public function editDiseaseRecord($recordId, array $data, $userId): array
    {
        try {
            DB::beginTransaction();
            $diseaseRecord = DiseaseRecord::find($recordId);
            if (!$diseaseRecord) {
                DB::rollBack();
                LogActionService::logAction(
                    $userId,
                    'delete',
                    'Disease',
                    $recordId,
                    ['error' => 'Disease Record not found'],
                    "Failed to delete disease record: Disease Record not found",
                    false
                );
                return [false, 'Disease Record not found.', []];
            }
            $oldData = $diseaseRecord->toArray();
            $existingData = $diseaseRecord->data;

            $diseaseId = $data['diseaseId'];
            unset($data['diseaseId']);

            [$schema, $message] = $this->diseaseService->getSchemaField($diseaseId);
            
            foreach ($data['data'] as $key => $value) {

                $fieldSchema = collect($schema)->firstWhere('name', $key);

                if (!$fieldSchema) {
                    throw new \Exception("Field '{$key}' is not defined in the schema.");
                }

                if ($fieldSchema['type'] === 'file') {
                    $isMultiple = $fieldSchema['multiple'] ?? false;
    
                    if ($isMultiple) {
                        $value = is_array($value) ? $value : [$value];
                        $fileUrls = [];
    
                        // Delete old files if they exist
                        if (isset($existingData[$key]) && is_array($existingData[$key])) {
                            foreach ($existingData[$key] as $oldFile) {
                                $this->fileStorage->deleteFile($oldFile, true);
                            }
                        }
    
                        // Store new files
                        foreach ($value as $index => $file) {
                            if ($file && is_file($file)) {
                                $fileUrls[] = $this->fileStorage->storeRecordFile(
                                    $file,
                                    $diseaseId,
                                    $key . '-' . ($index + 1)
                                );
                            }
                        }
    
                        $existingData[$key] = $fileUrls;
                    } else {
                        // Single file handling
                        if (isset($existingData[$key])) {
                            // echo($existingData[$key]);
                            $this->fileStorage->deleteFile($existingData[$key], true);
                        }
    
                        if (is_file($value)) {
                            $existingData[$key] = $this->fileStorage->storeRecordFile(
                                $value,
                                $diseaseId,
                                $key
                            );
                        } else {
                            $existingData[$key] = null; // Clear the field if no new file is provided
                        }
                    }
                } else {
                    // Non-file fields
                    $existingData[$key] = $value;
                }
            }
            
            $diseaseRecord->update([
                'data' => $existingData,
            ]);
            
            DB::commit();

            LogActionService::logAction(
                $userId,
                'edit',
                'DiseaseRecord',
                $diseaseRecord->id,
                [
                    'old' => $oldData,
                    'new' => $diseaseRecord->toArray(),
                    'changed_fields' => array_keys($data['data'])
                ],
                "Updated disease record ID: {$recordId}",
                true
            );
            return [true, 'Disease record updated successfully.', $diseaseRecord->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                $userId,
                'edit',
                'DiseaseRecord',
                $recordId,
                [
                    'attempted_changes' => array_keys($data['data']),
                    'error' => $exception->getMessage()
                ],
                "Failed to update disease record: {$exception->getMessage()}",
                false
            );
    
            return [false, 'Disease record update failed: ' . $exception->getMessage(), []];
        }
    }
    
    public function deleteDiseaseRecord($recordId, $userId): array
    {
        try {
            DB::beginTransaction();
            $diseaseRecord = DiseaseRecord::find($recordId);
            $diseaseId = $diseaseRecord->disease_id;

            if (!$diseaseRecord) {
                DB::rollBack();
                LogActionService::logAction(
                    $userId,
                    'delete',
                    'Disease',
                    $recordId,
                    ['error' => 'Disease Record not found'],
                    "Failed to delete disease record: Disease Record not found",
                    false
                );
                return [false, 'Disease Record not found.', []];
            }

            $recordInfo = [
                'disease_id' => $diseaseId,
                'data_fields' => array_keys($diseaseRecord->data),
                'file_count' => collect($diseaseRecord->data)
                    ->flatten()
                    ->filter(function($value) {
                        return is_string($value) && file_exists(storage_path("app/public/{$value}"));
                    })
                    ->count()
            ];

            [$schema, $message] = $this->diseaseService->getSchemaField($diseaseId);
            $recordData = $diseaseRecord->data;

            $this->deleteRecordFiles($recordData, $schema);
            
            $diseaseRecord->delete();
            
            DB::commit();

            LogActionService::logAction(
                $userId,
                'delete',
                'DiseaseRecord',
                $recordId,
                $recordInfo,
                "Deleted disease record ID: {$recordId}",
                true
            );
            return [true, 'Disease record deleted successfully.', []];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                $userId,
                'delete',
                'DiseaseRecord',
                $recordId,
                ['error' => $exception->getMessage()],
                "Failed to delete disease record: {$exception->getMessage()}",
                false
            );
    
            return [false, 'Disease record deletion failed: ' . $exception->getMessage(), []];
        }
    }

    private function transformFileFields(array $data, array $schema): array
    {
        foreach ($schema as $field) {
            // print_r($field);
            if ($field['type'] === 'file' && isset($data[$field['name']])) {
                // Handle single file
                if (empty($field['multiple'])) {
                    $data[$field['name']] = $this->generatePublicFileUrl($data[$field['name']]);
                } else {
                    // Handle multiple files
                    $data[$field['name']] = array_map(function ($filePath) {
                        return $this->generatePublicFileUrl($filePath);
                    }, $data[$field['name']]);
                }
            }
        }
    
        return $data;
    }
    

    /**
     * Generate a public URL for a given file path.
     */
    private function generatePublicFileUrl(string $filePath): string
    {
        return Storage::url('public/' . $filePath);
        //return asset('storage/' . $filePath);
    }

    public function getDiseaseRecords($diseaseId, array $filters)
    {
        try {
            [$schema, $message] = $this->diseaseService->getSchemaField($diseaseId);

            $disease = Disease::findOrFail($diseaseId);

            if (empty($schema)) {
                if ($message === 'Access denied. This disease is private.'){
                    return [false, 'Cant Access Private Disease', []];
                } 
                return [false, 'Schema not found for this disease.', []];
            }

            $query = $this->buildDiseaseRecordQuery($diseaseId, $filters);
            $paginatedData = $this->paginateResults($query, $filters);

            // Check if records exist and transform them
            $records = !empty($paginatedData['records'])
                ? array_map(function ($record) use ($schema) {
                    $record['data'] = $this->transformFileFields($record['data'], $schema);
                    return $record;
                }, $paginatedData['records'])
                : [];

            $response = [
                'name' => $disease['name'],
                'deskripsi' => $disease['deskripsi'],
                'schema' => $schema,
                'export_url' => $disease->export_url,
                'records' => $records,
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
            [$schema, $message] = $this->diseaseService->getSchemaField($diseaseId);

            if (empty($schema)) {
                return [false, 'Schema not found for this disease.', []];
            }

            $record = DiseaseRecord::where('disease_id', $diseaseId)
                ->where('id', $recordId)
                ->firstOrFail();

            // Transform file fields in the record
            $record->data = $this->transformFileFields($record->data, $schema);

            $response = [
                'schema' => $schema,
                'record' => $record->toArray(),
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
        if(!empty($filters['order_by'])){
            if($filters['order_by'] === 'desc'){
                $query->orderBy('created_at', 'desc');
            }else if($filters['order_by'] === 'desc'){
                $query->orderBy('created_at', 'asc');
            }
        }
        
        $query->orderBy('updated_at', 'desc');
        
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

    public function deleteRecordFiles(array $data, array $schema): void
    {
        foreach ($data as $key => $value) {
            $fieldSchema = collect($schema)->firstWhere('name', $key);

            // Only proceed if this is a file field
            if ($fieldSchema && $fieldSchema['type'] === 'file') {
                if (is_array($value)) {
                    // Multiple files
                    foreach ($value as $filePath) {
                        $this->fileStorage->deleteFile($filePath, true);
                    }
                } elseif (is_string($value)) {
                    // Single file
                    $this->fileStorage->deleteFile($value, true);
                }
            }
        }
    }
}
