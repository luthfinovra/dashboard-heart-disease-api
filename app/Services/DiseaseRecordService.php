<?php

namespace App\Services;

use App\Models\DiseaseRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class DiseaseRecordService
{
    protected $diseaseService;
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    public function __construct(DiseaseService $diseaseService)
    {
        $this->diseaseService = $diseaseService;
    }

    public function createDiseaseRecord(array $data): array
    {
        try {
            DB::beginTransaction();

            $diseaseId = $data['diseaseId'];
            unset($data['diseaseId']);
    
            $diseaseRecord = DiseaseRecord::create([
                'disease_id' => $diseaseId,
                'data' => $data,
            ]);

            DB::commit();
            return [true, 'Disease record created successfully.', $diseaseRecord->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            return [false, 'Disease record creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function editDiseaseRecord($id, array $data): array
    {
        try {
            DB::beginTransaction();

            $diseaseRecord = DiseaseRecord::find($id);
            if (!$diseaseRecord) {
                return [false, 'Disease record not found.', []];
            }

            $diseaseRecord->update([
                'data' => $data['data'],
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
            $diseaseRecord = DiseaseRecord::findOrFail($id);
            $diseaseRecord->delete();

            return [true, 'Disease record deleted successfully.', []];
        } catch (\Throwable $exception) {
            return [false, 'Disease record deletion failed: ' . $exception->getMessage(), []];
        }
    }

    public function getDiseaseRecords($diseaseId, array $filters)
    {
        try {
            $schema = $this->diseaseService->getSchemaField($diseaseId);
            
            if (empty($schema)) {
                return [false, 'Schema not found for this disease.', []];
            }

            $query = $this->buildDiseaseRecordQuery($diseaseId, $filters);
            $paginatedData = $this->paginateResults($query, $filters);

            $response = [
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
            $record = DiseaseRecord::where('disease_id', $diseaseId)
                ->where('id', $recordId)
                ->firstOrFail();

            return [true, 'Disease record details retrieved successfully.', $record];
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
}
