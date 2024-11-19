<?php

namespace App\Http\Controllers;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use App\Models\DiseaseRecord;
use App\Models\Disease;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
        $this->middleware(['auth:sanctum', 'checkDiseaseAccess']);
    }

    public function previewFile(Request $request, string $path)
    {
        try {
            $path = $this->sanitizePath($path);
            $storagePath = storage_path('app/public/' . $path);

            if (!file_exists($storagePath)) {
                Log::error('File not found', ['path' => $path]);
                abort(404, 'File not found');
            }

            // Determine MIME type
            $mimeType = mime_content_type($storagePath);

            // Only allow audio files for preview (extendable later)
            if (!str_starts_with($mimeType, 'audio/')) {
                abort(415, 'Unsupported media type for inline preview');
            }

            // Stream audio file
            return response()->file($storagePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline',
            ]);
        } catch (\Exception $e) {
            Log::error('File preview failed', [
                'error' => $e->getMessage(),
                'path' => $path,
                'user_id' => $request->user()->id ?? null,
            ]);
            abort(500, 'Failed to preview file');
        }
    }

    public function downloadRecord(Request $request, string $path)
    {
        try {
            // Clean up the path to match storage structure
            $path = $this->sanitizePath($path);
            
            // Determine the full storage path
            $storagePath = storage_path('app/public/' . $path);
            
            if (!file_exists($storagePath)) {
                Log::error('File not found', [
                    'path' => $path,
                    'full_path' => $storagePath
                ]);
                abort(404, 'File not found');
            }

            // Find record containing this file path
            $record = $this->findRecordByPath($path);
            if (!$record) {
                abort(404, 'Associated record not found');
            }

            // Log download attempt
            Log::info('File download initiated', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'disease_id' => $record->disease_id,
                'record_id' => $record->id
            ]);

            return $this->fileStorage->streamFile('public/' . $path);

        } catch (\Exception $e) {
            Log::error('File download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'path' => $path ?? 'undefined',
                'user_id' => $request->user()->id ?? 'undefined'
            ]);

            abort(500, 'Failed to download file: ' . $e->getMessage());
        }
    }

    private function sanitizePath(string $path): string
    {
        // Remove any directory traversal attempts and extra slashes
        $path = str_replace(['..', '//'], ['', '/'], $path);
        
        // Remove /api/files/records if present at the start
        $path = preg_replace('/^\/?(api\/files\/records\/)?/', '', $path);
        
        return trim($path, '/');
    }

    private function findRecordByPath(string $path): ?DiseaseRecord
    {
        // First find all diseases that have file fields
        $diseasesWithFiles = Disease::whereRaw("jsonb_path_exists(schema->'columns', 
            '$.** ? (@.type == \"file\")')")
            ->get();

        if ($diseasesWithFiles->isEmpty()) {
            return null;
        }

        // Get all file field names from the schemas
        $fileFields = [];
        foreach ($diseasesWithFiles as $disease) {
            $columns = $disease->schema['columns'] ?? [];
            foreach ($columns as $column) {
                if (($column['type'] ?? '') === 'file') {
                    $fileFields[$disease->id][] = [
                        'name' => $column['name'],
                        'multiple' => $column['multiple'] ?? false
                    ];
                }
            }
        }

        // Build query based on discovered file fields
        return DiseaseRecord::where(function ($query) use ($path, $fileFields) {
            foreach ($fileFields as $diseaseId => $fields) {
                foreach ($fields as $field) {
                    if ($field['multiple']) {
                        // Handle array fields using ? operator
                        $query->orWhere(function ($q) use ($path, $diseaseId, $field) {
                            $q->where('disease_id', $diseaseId)
                              ->whereRaw("data->? ?? ?", [$field['name'], $path]);
                        });
                    } else {
                        // Handle single file fields
                        $query->orWhere(function ($q) use ($path, $diseaseId, $field) {
                            $q->where('disease_id', $diseaseId)
                              ->whereRaw("data->>? = ?", [$field['name'], $path]);
                        });
                    }
                }
            }
        })->first();
    }
}