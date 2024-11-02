<?php

namespace App\Services;

use App\Models\Disease;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDiseaseService
{
    public function createDisease(array $data): array
    {
        try {
            $disease = Disease::create($data);
            return [true, 'Disease created successfully.', $disease];
        } catch (\Throwable $exception) {
            // Log exception (optional)
            return [false, 'Failed to create disease: ' . $exception->getMessage(), []];
        }
    }
}
