<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use App\Models\DiseaseRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\FileStorageService;
use App\Http\Requests\IndexDiseaseRecordRequest;
use App\Http\Requests\ShowDiseaseRecordRequest;
use Illuminate\Support\Facades\URL;

class DiseaseRecordExportController extends Controller
{
    protected $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
        $this->middleware(['auth:sanctum', 'checkDiseaseAccess']);
    }

    /**
     * Export all disease records to CSV
     */
    public function exportToCsv(IndexDiseaseRecordRequest $request, $diseaseId)
    {
        // Validate disease exists
        $disease = Disease::findOrFail($diseaseId);
        
        // Get all columns from schema
        $columns = collect($disease->schema['columns'])
            ->pluck('name')
            ->toArray();

        // Generate filename
        $filename = sprintf(
            'disease-records-%s-%s.csv',
            $disease->name,
            now()->format('Y-m-d-His')
        );

        return $this->generateCsvResponse($disease, $columns, $filename);
    }

    /**
     * Export single record to CSV
     */
    public function exportSingleRecord(ShowDiseaseRecordRequest $request, $diseaseId, $recordId)
    {
        $disease = Disease::findOrFail($diseaseId);
        $record = DiseaseRecord::where('disease_id', $diseaseId)
            ->where('id', $recordId)
            ->firstOrFail();

        $columns = collect($disease->schema['columns'])
            ->pluck('name')
            ->toArray();

        $filename = sprintf(
            'disease-record-%s-%d-%s.csv',
            $disease->name,
            $recordId,
            now()->format('Y-m-d-His')
        );

        return $this->generateCsvResponse($disease, $columns, $filename, $record);
    }

    /**
     * Generate CSV response for both single and multiple records
     */
    private function generateCsvResponse($disease, $columns, $filename, $singleRecord = null)
    {
        return new StreamedResponse(function () use ($disease, $columns, $singleRecord) {
            $handle = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($handle, [...$columns, 'created_at', 'updated_at']);

            if ($singleRecord) {
                // Write single record
                $this->writeRecordToCsv($handle, $singleRecord, $columns);
            } else {
                // Write all records
                DiseaseRecord::where('disease_id', $disease->id)
                    ->chunk(100, function ($records) use ($handle, $columns) {
                        foreach ($records as $record) {
                            $this->writeRecordToCsv($handle, $record, $columns);
                        }
                    });
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
    
    private function generateSignedFileUrl($path)
    {
        // Remove any leading slashes
        $path = ltrim($path, '/');
        
        return URL::temporarySignedRoute(
            'files.download.record',
            now()->addHours(24), // URLs valid for 24 hours
            ['path' => $path]
        );
    }

    private function generatePublicFileUrl($path)
    {
        $domain = env('APP_URL');
        return $domain . '/storage/public/' . $path;
    }
    /**
     * Write a single record to CSV
     */
    private function writeRecordToCsv($handle, $record, $columns)
    {
        $row = [];
        foreach ($columns as $column) {
            $value = $record->data[$column] ?? '';
            
            // Handle file paths
            if (is_array($value)) {
                // Convert array of file paths to public URLs
                $urls = array_map(
                    fn($path) => $this->generatePublicFileUrl($path),
                    $value
                );
                $value = implode(' | ', $urls);
            } elseif (is_string($value) && str_contains($value, 'diseases/records/')) {
                // Convert single file path to public URL
                $value = $this->generatePublicFileUrl($value);
            }
            
            $row[] = $value;
        }
        
        // Add timestamps
        $row[] = $record->created_at->format('Y-m-d H:i:s');
        $row[] = $record->updated_at->format('Y-m-d H:i:s');
        
        fputcsv($handle, $row);
    }
}