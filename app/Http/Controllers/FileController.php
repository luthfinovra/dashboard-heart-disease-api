<?php
namespace App\Http\Controllers;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use App\Models\DiseaseRecord;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    protected $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
        $this->middleware(['auth:sanctum', 'checkDiseaseAccess']);
    }

    public function downloadRecord(Request $request, string $path)
    {
        // Sanitize the path to prevent directory traversal
        $path = $this->sanitizePath($path);

        // Find the record and verify user has access to it
        $record = DiseaseRecord::whereJsonContains('data', $path)->firstOrFail();

        // Additional permission check for the specific record
        if (!$this->userCanAccessRecord($request->user(), $record)) {
            abort(403, 'You do not have permission to access this file.');
        }

        // Verify file exists and is within allowed directory
        if (!$this->isFileAccessAllowed($path)) {
            abort(404, 'File not found or access denied.');
        }

        try {
            $response = $this->fileStorage->streamFile($path);
            
            if (!$response) {
                abort(404, 'File not found.');
            }

            // Add security headers
            return $this->addSecurityHeaders($response);
            
        } catch (\Exception $e) {
            Log::error('File download failed: ' . $e->getMessage(), [
                'path' => $path,
                'user_id' => $request->user()->id,
                'record_id' => $record->id
            ]);
            abort(500, 'Failed to download file.');
        }
    }

    private function sanitizePath(string $path): string
    {
        // Remove any directory traversal attempts
        $path = str_replace(['../', '..\\'], '', $path);
        
        // Ensure path starts with diseases/records/
        if (!str_starts_with($path, 'diseases/records/')) {
            abort(404);
        }

        return $path;
    }

    private function isFileAccessAllowed(string $path): bool
    {
        // Check if file exists
        if (!Storage::exists($path)) {
            return false;
        }

        // Verify file is in allowed directory
        $realPath = Storage::path($path);
        $storagePath = Storage::path('diseases/records');

        // Ensure file is actually within the records directory
        return str_starts_with($realPath, $storagePath);
    }

    private function userCanAccessRecord($user, DiseaseRecord $record): bool
    {
        // Add your specific access control logic here
        // For example:
        return $user->can('view', $record) || 
               $user->hasRole('admin') || 
               $record->disease->users->contains($user->id);
    }

    private function addSecurityHeaders(StreamedResponse $response): StreamedResponse
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
        
    }
}