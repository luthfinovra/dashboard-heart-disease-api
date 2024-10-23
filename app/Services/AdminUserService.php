<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function createUser(array $data): array
    {
        try {
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

            return [true, 'User created successfully.', $user];
        } catch (\Throwable $exception) {
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

            return [true, 'User rejected successfully.', []];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'User rejection failed: ' . $exception->getMessage(), []];
        }
    }

    public function editUser($id, array $data): array
    {
        try {
            $user = User::findOrFail($id);

             // Check if password is provided
            if (isset($data['password']) && $data['password']) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            return [true, 'User updated successfully.', $user];
        } catch (\Throwable $exception) {
            // TO DO logging
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

        $users = $query->paginate(10); 

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
}
