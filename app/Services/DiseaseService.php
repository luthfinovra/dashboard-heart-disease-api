<?php

namespace App\Services;

use App\Models\Disease;
use App\Http\Requests\CreateDiseaseRequest;
use Illuminate\Support\Facades\DB;

class DiseaseService
{
    public function createDisease(array $data): array
    {
        try {
            $image_url = 'test';

            $disease = Disease::create([
                'name' => $data['name'],
                'deskripsi' => $data['deskripsi'],
                'schema' => $data['schema'], // TO DO Validation

                // TO DO
                'cover_page' => $image_url,
            ]);

            return [true, 'Disease created successfully.', $disease->toArray()];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'Disease creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function editDisease($id, array $data): array
    {
        try {
            $disease = Disease::find($id);
            if (!$disease) {
                return [false, 'Disease not found.', []];
            }

            // TO DO
            $disease->update($data);

            // if ($user->role === 'operator' && array_key_exists('disease_ids', $data)) {
            //     $user->managedDiseases()->sync($data['disease_ids']);
            // }

            return [true, 'Disease updated successfully.', $disease];
        } catch (\Throwable $exception) {
            // TO DO: Add logging here
            return [false, 'Disease update failed: ' . $exception->getMessage(), []];
        }
    }


    public function deleteDisease($id): array
    {
        try {
            $disease = Disease::findOrFail($id);
            $disease->delete();

            return [true, 'Disease deleted successfully.', []];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'Disease deletion failed: ' . $exception->getMessage(), []];
        }
    }

    public function getDisease(array $filters): array
    {
        try{
        $query = Disease::query();

        // if (isset($filters['role'])) {
        //     $query->where('role', $filters['role']);
        // }

        // if (isset($filters['name'])) {
        //     $query->where('name', 'like', '%' . $filters['name'] . '%');
        // }

        // if (isset($filters['approval_status'])) {
        //     $query->where('approval_status', $filters['approval_status']);
        // }

        $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) && $filters['per_page'] > 0 
        ? (int) $filters['per_page'] 
        : 10;

        $diseases = $query->paginate($perPage); 

        $paginatedData = [
            'diseases' => $diseases->items(),
            'current_page' => $diseases->currentPage(),
            'last_page' => $diseases->lastPage(),
            'per_page' => $diseases->perPage(),
            'total' => $diseases->total(),
        ];

        return [true, 'Diseases retrieved successfully.', $paginatedData];
    } catch (\Throwable $exception){
        // TO DO Logging
        return [false, 'Failed to retrieve diseases data: ' . $exception->getMessage(), []];
    }
    }


    public function getDiseaseDetails($id): array
    {
        try {
            $disease = Disease::findOrFail($id);
            return [true, 'Disease details retrieved successfully.', $disease];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'Failed to retrieve disease details: ' . $exception->getMessage(), []];
        }
    }

}
