<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserService
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    public function createUser(array $data): array
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'approval_status' => 'approved',
                'institution' => $data['institution'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
            ]);

            if ($user->role === 'operator' && isset($data['disease_ids'])) {
                $user->managedDiseases()->attach($data['disease_ids']);
            }

            DB::commit();
            return [true, 'User created successfully.', $user->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            // TO DO logging
            return [false, 'User creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function approveUser($id): array
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($id);
            $user->approval_status = 'approved';
            $user->save();

            DB::commit();
            return [true, 'User approved successfully.', $user];
        } catch (\Throwable $exception) {
            // TO DO logging
            DB::rollBack();

            return [false, 'User approval failed: ' . $exception->getMessage(), []];
        }
    }

    public function rejectUser($id): array
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($id);
            $user->approval_status = 'rejected';
            $user->save();

            DB::commit();
            return [true, 'User rejected successfully.', $user];
        } catch (\Throwable $exception) {
            // TO DO logging
            DB::rollBack();
            return [false, 'User rejection failed: ' . $exception->getMessage(), []];
        }
    }

    public function editUser($id, array $data): array
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);
            if (!$user) {
                return [false, 'User not found.', []];
            }

            if (isset($data['password']) && $data['password']) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            if (isset($data['email']) && $data['email'] !== $user->email) {
                $existingUser = User::where('email', $data['email'])->first();
                if ($existingUser) {
                    return [false, 'Email address is already in use.', []];
                }
            }

            $user->update($data);

            if ($user->role === 'operator' && array_key_exists('disease_ids', $data)) {
                $user->managedDiseases()->sync($data['disease_ids']);
            }

            DB::commit();
            return [true, 'User updated successfully.', $user];
        } catch (\Throwable $exception) {
            DB::rollBack();
            // TO DO: Add logging here
            return [false, 'User update failed: ' . $exception->getMessage(), []];
        }
    }



    public function deleteUser($id): array
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($id);
            if ((int) auth()->id() === (int) $id) {
                return [false, 'You cannot delete your own account.', []];
            }
    
            if ($user->is_primary_admin) {
                return [false, 'The primary admin account cannot be deleted.', []];
            }
    
            $user->delete();

            DB::commit();
            return [true, 'User deleted successfully.', []];
        } catch (\Throwable $exception) {
            // Log the exception
            DB::rollBack();
            return [false, 'User deletion failed: ' . $exception->getMessage(), []];
        }
    }

    public function getUsers(array $filters): array
    {
        try {
            $query = User::query();
            
            $this->applyUserFilters($query, $filters);
            
            $paginatedData = $this->paginateResults($query, $filters, 'users');

            return [true, 'Users retrieved successfully.', $paginatedData];
        } catch (\Throwable $exception) {
            return [false, 'Failed to retrieve users: ' . $exception->getMessage(), []];
        }
    }


    public function getUserDetails($id): array
    {
        try {
            $user = User::findOrFail($id);
            return [true, 'User details retrieved successfully.', $user];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'Failed to retrieve user details: ' . $exception->getMessage(), []];
        }
    }

    public function assignOperatorToDiseases(int $userId, array $diseaseIds): array
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($userId);

            $user->managedDiseases()->sync($diseaseIds);

            DB::commit();
            return [true, 'Diseases assigned to operator successfully.', $user];
        } catch (\Throwable $exception) {
            // Log exception
            DB::rollBack();
            return [false, 'Failed to assign diseases to operator: ' . $exception->getMessage(), []];
        }
    }

    private function applyUserFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . trim($filters['name']) . '%');
        }

        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        // Add sorting if needed
        $query->orderBy('created_at', 'desc');
    }

    private function paginateResults(Builder $query, array $filters, string $itemsKey): array
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
            $itemsKey => $results->items(),
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
