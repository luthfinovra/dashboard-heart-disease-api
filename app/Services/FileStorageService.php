<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    public function storeFile($file, string $directory, ?string $filename = null, bool $isPublic = false): string
    {
        if (!$filename) {
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        }
        
        $baseDirectory = $isPublic ? "public/$directory" : $directory;
        $path = $file->storeAs($baseDirectory, $filename);
        
        return $isPublic ? str_replace('public/', '', $path) : $path;
    }

    public function storeCoverImage($file, int $diseaseId): string
    {
        $filename = "disease-{$diseaseId}-cover." . $file->getClientOriginalExtension();
        return $this->storeFile($file, 'diseases/covers', $filename, true);
    }

    public function storeRecordFile($file, int $diseaseId, string $fieldName): string
    {
        $filename = Str::slug($fieldName) . '-' . time() . '.' . $file->getClientOriginalExtension();
        return $this->storeFile($file, "diseases/records/$diseaseId", $filename, true);
    }

    public function deleteFile(?string $path, bool $isPublic = false): bool
    {
        if (!$path) return true;
        
        $fullPath = $isPublic ? 'public/' . $path : $path;
        return Storage::delete($fullPath);
    }

    public function streamFile(string $filePath): StreamedResponse
    {
        return response()->streamDownload(function () use ($filePath) {
            $stream = fopen(storage_path('app/' . $filePath), 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            } else {
                abort(404, 'File not found');
            }
        }, basename($filePath));
    }

    public function deleteDirectory(string $directory, bool $isPublic = false): bool
    {
        $fullPath = $isPublic ? 'public/' . $directory : $directory;

        $fullPathResolved = realpath(storage_path('app/' . $fullPath));
        $allowedDirectory = realpath(storage_path('app/diseases/'));

        if (strpos($fullPathResolved, $allowedDirectory) !== 0) {
            throw new \Exception('Directory traversal attempt detected!');
        }
        
        if(Storage::exists($fullPath)){
            Storage::deleteDirectory($fullPath);
        }

        return !Storage::exists($fullPath);
    }
}