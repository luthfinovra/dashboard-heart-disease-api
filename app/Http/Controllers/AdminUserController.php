<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson; // Import the ResponseJson helper
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\EditUserRequest;
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

    public function approveUser($id): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->approveUser($id);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User approved successfully.', $data);
    }

    public function rejectUser($id): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->rejectUser($id);

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

    public function deleteUser($id): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->deleteUser($id);

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

    public function getUserDetails($id): JsonResponse
    {
        [$success, $message, $data] = $this->adminUserService->getUserDetails($id);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('User details retrieved successfully.', $data);
    }
}
