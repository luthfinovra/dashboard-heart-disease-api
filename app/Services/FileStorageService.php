<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    /**
     * Store a file and return its relative path
     */
    public function storeFile($file, string $directory, ?string $filename = null, bool $isProtected = false): string
    {
        if (!$filename) {
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        }
        
        $baseDirectory = $isProtected ? $directory : "public/$directory";
        $path = $file->storeAs($baseDirectory, $filename);
        
        return $isProtected ? $path : str_replace('public/', '', $path);
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(?string $path, bool $isProtected = false): bool
    {
        if (!$path) return true;
        
        $fullPath = $isProtected ? $path : 'public/' . $path;
        return Storage::delete($fullPath);
    }

    /**
     * Get the full path for a file
     */
    public function getFullPath(string $path, bool $isProtected = false): string
    {
        return $isProtected ? storage_path('app/' . $path) : Storage::url($path);
    }
}