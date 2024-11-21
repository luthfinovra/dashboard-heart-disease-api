<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson; // Import the ResponseJson helper
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\EditUserRequest;
use App\Http\Requests\UserIdRequest;
use App\Services\AdminUserService;
use App\Services\DiseaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    protected AdminUserService $adminUserService;

    public function __construct(AdminUserService $adminUserService)
    {
        $this->adminUserService = $adminUserService;
    }

    public function createUser(CreateUserRequest $request): JsonResponse
    {

        $validatedData = $request->validated();

        [$success, $message, $data] = $this->adminUserService->createUser($validatedData);

        if(!$success){
            return ResponseJson::failedResponse($message, $validatedData);
        }

        if ($validatedData['role'] === 'operator' && isset($validatedData['disease_ids'])) {
            [$assignSuccess, $assignMessage, $data] = $this->adminUserService->assignOperatorToDiseases($data['id'], $validatedData['disease_ids']);
            
            if ($assignSuccess) {
                $message .= ' ' . $assignMessage;
            } else {
                return ResponseJson::failedResponse($assignMessage);
            }
        }

        return ResponseJson::successResponse($message, $data);
    }

    public function approveUser(UserIdRequest $request, $userId): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->approveUser($userId);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User approved successfully.', $data);
    }

    public function rejectUser(UserIdRequest $request, $userId): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->rejectUser($userId);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User rejected successfully.', $data);
    }

    public function editUser(EditUserRequest $request, $id): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->editUser($id, $request->validated());

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User updated successfully.', $data);
    }

    public function deleteUser(UserIdRequest $request, $userId): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->deleteUser($userId);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User deleted successfully.', $data);
    }

    public function getUsers(Request $request): JsonResponse
    {
        [$success, $message, $data]= $this->adminUserService->getUsers($request->all());

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }
        return ResponseJson::successResponse('Users retrieved successfully.', $data);
    }

    public function getUserDetails(UserIdRequest $request, $userId): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->getUserDetails($userId);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User details retrieved successfully.', $data);
    }

    public function getUserProfile(Request $request)
    {
        $user = $request->user(); // Get the authenticated user
        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'institution' => $user->institution,
                'gender' => $user->gender,
                'phone' => $user->phone_number,
                'approval_status' => $user->approval_status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }
}
