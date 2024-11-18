<?php
// app/Services/FileStorageService.php
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
        return $this->storeFile($file, "diseases/records/$diseaseId", $filename);
    }

    public function deleteFile(?string $path, bool $isPublic = false): bool
    {
        if (!$path) return true;
        
        $fullPath = $isPublic ? 'public/' . $path : $path;
        return Storage::delete($fullPath);
    }

    public function streamFile(string $path): ?StreamedResponse
    {
        if (!Storage::exists($path)) {
            return null;
        }

        $mime = Storage::mimeType($path);
        $filename = basename($path);

        return Storage::response($path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}