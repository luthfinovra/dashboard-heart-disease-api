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
            DB::beginTransaction();
            
            $image_url = null;
            // if (isset($data['cover_page']) && $data['cover_page']) {
            //     $file = $data['cover_page'];
            //     $filename = Str::slug($data['name']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            //     $path = $file->storeAs('public/diseases/covers', $filename);
            //     $image_url = Storage::url($path);
            // }

            $disease = Disease::create([
                'name' => $data['name'],
                'deskripsi' => $data['deskripsi'],
                'schema' => $data['schema'],
                'cover_page' => $image_url,
            ]);

            DB::commit();
            return [true, 'Disease created successfully.', $disease->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            // TO DO logging
            return [false, 'Disease creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function editDisease($id, array $data): array
    {
        try {
            DB::beginTransaction();

            $disease = Disease::find($id);
            if (!$disease) {
                return [false, 'Disease not found.', []];
            }

            // if (isset($data['cover_page']) && $data['cover_page']) {
            //     // Delete old cover if exists
            //     if ($disease->cover_page) {
            //         $oldPath = str_replace('/storage', 'public', $disease->cover_page);
            //         Storage::delete($oldPath);
            //     }

            //     $file = $data['cover_page'];
            //     $filename = Str::slug($data['name']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            //     $path = $file->storeAs('public/diseases/covers', $filename);
            //     $data['cover_page'] = Storage::url($path);
            // } else {
            //     unset($data['cover_page']);
            // }

            $disease->update($data);

            DB::commit();
            return [true, 'Disease updated successfully.', $disease];
        } catch (\Throwable $exception) {
            DB::rollBack();
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
        try {
            $query = Disease::query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            // Search in schema
            if (isset($filters['column_type'])) {
                $query->whereJsonContains('schema->columns', ['type' => $filters['column_type']]);
            }

            if (isset($filters['column_name'])) {
                $query->whereJsonContains('schema->columns', ['name' => $filters['column_name']]);
            }

            $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) && $filters['per_page'] > 0 
                ? (int) $filters['per_page'] 
                : 10;

            $diseases = $query->paginate($perPage);

            return [true, 'Diseases retrieved successfully.', [
                'diseases' => $diseases->items(),
                'current_page' => $diseases->currentPage(),
                'last_page' => $diseases->lastPage(),
                'per_page' => $diseases->perPage(),
                'total' => $diseases->total(),
            ]];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve diseases: ' . $exception->getMessage(), []];
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
