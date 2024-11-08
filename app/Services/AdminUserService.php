<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserService
{
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
            $user = User::findOrFail($id);
            $user->approval_status = 'approved';
            $user->save();

            return [true, 'User approved successfully.', $user];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'User approval failed: ' . $exception->getMessage(), []];
        }
    }

    public function rejectUser($id): array
    {
        try {
            $user = User::findOrFail($id);
            $user->approval_status = 'rejected';
            $user->save();

            return [true, 'User rejected successfully.', $user];
        } catch (\Throwable $exception) {
            // TO DO logging
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
            $user = User::findOrFail($id);
            $user->delete();

            return [true, 'User deleted successfully.', []];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'User deletion failed: ' . $exception->getMessage(), []];
        }
    }

    public function getUsers(array $filters): array
    {
        $query = User::query();

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) && $filters['per_page'] > 0 
        ? (int) $filters['per_page'] 
        : 10;

        $users = $query->paginate($perPage); 

        $paginatedData = [
            'users' => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ];

        return [true, 'Users retrieved successfully.', $paginatedData];
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
            $user = User::findOrFail($userId);

            $user->managedDiseases()->sync($diseaseIds);

            return [true, 'Diseases assigned to operator successfully.', $user];
        } catch (\Throwable $exception) {
            // Log exception
            return [false, 'Failed to assign diseases to operator: ' . $exception->getMessage(), []];
        }
    }
}
