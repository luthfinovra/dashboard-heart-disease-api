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

            if ($user->role === 'operator' && isset($data['disease_id'])) {
                $user->managedDiseases()->attach($data['disease_id']);
            }

            DB::commit();

            LogActionService::logAction(
                auth()->id(),
                'create',
                'User',
                $user->id,
                $user->toArray(),
                "Created user: {$user->name}",
                true
            );
            return [true, 'User created successfully.', $user->toArray()];
        } catch (\Throwable $exception) {
            DB::rollBack();
            
            LogActionService::logAction(
                auth()->id(),
                'create',
                'User',
                null,
                [
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'error' => $exception->getMessage()
                ],
                "Failed to create user: {$exception->getMessage()}",
                false
            );
            return [false, 'User creation failed: ' . $exception->getMessage(), []];
        }
    }

    public function approveUser($id): array
    {
        try {
            DB::beginTransaction();
            $user = User::find($id);
            if (!$user) {
                DB::rollBack();
                LogActionService::logAction(
                    $user,
                    'edit',
                    'User',
                    $id,
                    ['error' => 'User not found'],
                    "Failed to approve User: User not found",
                    false
                );
                return [false, 'User not found.', []];
            }

            $oldStatus = $user->approval_status;
            $user->approval_status = 'approved';
            $user->save();

            DB::commit();

            LogActionService::logAction(
                auth()->id(),
                'edit',
                'User',
                $user->id,
                [
                    'old_status' => $oldStatus,
                    'new_status' => 'approved'
                ],
                "Approved user: {$user->name}",
                true
            );
            return [true, 'User approved successfully.', $user];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                auth()->id(),
                'edit',
                'User',
                $id,
                ['error' => $exception->getMessage()],
                "Failed to approve user: {$exception->getMessage()}",
                false
            );

            return [false, 'User approval failed: ' . $exception->getMessage(), []];
        }
    }

    public function rejectUser($id): array
    {
        try {
            DB::beginTransaction();
            $user = User::find($id);
            if (!$user) {
                DB::rollBack();
                LogActionService::logAction(
                    $user,
                    'edit',
                    'User',
                    $id,
                    ['error' => 'User not found'],
                    "Failed to reject User: User not found",
                    false
                );
                return [false, 'User not found.', []];
            }

            $oldStatus = $user->approval_status;
            $user->approval_status = 'rejected';
            $user->save();

            DB::commit();

            LogActionService::logAction(
                auth()->id(),
                'edit',
                'User',
                $user->id,
                [
                    'old_status' => $oldStatus,
                    'new_status' => 'rejected'
                ],
                "Rejected user: {$user->name}",
                true
            );
            return [true, 'User rejected successfully.', $user];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                auth()->id(),
                'edit',
                'User',
                $id,
                ['error' => $exception->getMessage()],
                "Failed to reject user: {$exception->getMessage()}",
                false
            );
            return [false, 'User rejection failed: ' . $exception->getMessage(), []];
        }
    }

    public function editUser($id, array $data): array
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);
            if (!$user) {
                DB::rollBack();
                LogActionService::logAction(
                        auth()->id(),
                        'edit',
                        'User',
                        $id,
                        ['error' => 'User not found'],
                        "Failed to edit user: User not found",
                        false
                    );
                return [false, 'User not found.', []];
            }
            if (($user->is_primary_admin)) {
                DB::rollBack();
                LogActionService::logAction(
                    auth()->id(),
                    'edit',
                    'User',
                    $id,
                    ['error' => 'Attempted to edit primary admin account'],
                    "Failed to edit user: Cannot edit primary admin account",
                    false
                );
                return [false, 'The primary admin account cannot be edited.', []];
            }

            $oldData = $user->toArray();

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

            $changedFields = array_keys(array_diff_assoc($data, $oldData));
            
            // Get only the values that are actually changing
            
            $user->update($data);
            
            if ($user->role === 'operator' && isset($data['disease_id'])) {
                // Detach old disease (if any) and attach the new one
                $user->managedDiseases()->sync([$data['disease_id']]);
            }
            
            $relevantOldData = array_intersect_key($oldData, $data);
            $relevantNewData = array_intersect_key($data, $oldData);
            
            DB::commit();

            LogActionService::logAction(
                auth()->id(),
                'edit',
                'User',
                $user->id,
                [
                    'old' => $relevantOldData,
                    'new' => $relevantNewData,
                    'changed_fields' => $changedFields
                ],
                "Updated user: {$user->name}",
                true
            );
            return [true, 'User updated successfully.', $user];
        } catch (\Throwable $exception) {
            DB::rollBack();
            
            LogActionService::logAction(
                auth()->id(),
                'edit',
                'User',
                $id,
                [
                    'attempted_changes' => array_keys($data),
                    'error' => $exception->getMessage()
                ],
                "Failed to update user: {$exception->getMessage()}",
                false
            );
            return [false, 'User update failed: ' . $exception->getMessage(), []];
        }
    }



    public function deleteUser($id): array
    {
        try {
            DB::beginTransaction();
            $user = User::find($id);
            if (!$user) {
                DB::rollBack();
                LogActionService::logAction(
                        auth()->id(),
                        'delete',
                        'User',
                        $id,
                        ['error' => 'User not found'],
                        "Failed to delete user: User not found",
                        false
                    );
                return [false, 'User not found.', []];
            }

            if ((int) auth()->id() === (int) $id) {
                DB::rollBack();
                LogActionService::logAction(
                    auth()->id(),
                    'delete',
                    'User',
                    $id,
                    ['error' => 'Attempted to delete own account'],
                    "Failed to delete user: Cannot delete own account",
                    false
                );
                return [false, 'You cannot delete your own account.', []];
            }
    
            if ($user->is_primary_admin) {
                DB::rollBack();
                LogActionService::logAction(
                    auth()->id(),
                    'delete',
                    'User',
                    $id,
                    ['error' => 'Attempted to delete primary admin account'],
                    "Failed to delete user: Cannot delete primary admin account",
                    false
                );
                return [false, 'The primary admin account cannot be deleted.', []];
            }

            $userInfo = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ];

            $user->delete();

            DB::commit();

            LogActionService::logAction(
                auth()->id(),
                'delete',
                'User',
                $id,
                $userInfo,
                "Deleted user: {$user->name}",
                true
            );
            return [true, 'User deleted successfully.', []];
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogActionService::logAction(
                auth()->id(),
                'delete',
                'User',
                $id,
                ['error' => $exception->getMessage()],
                "Failed to delete user: {$exception->getMessage()}",
                false
            );
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
            $query->where('name', 'ILIKE', '%' . trim($filters['name']) . '%');
        }

        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        // Add sorting if needed
        $query->orderBy('updated_at', 'desc');
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
