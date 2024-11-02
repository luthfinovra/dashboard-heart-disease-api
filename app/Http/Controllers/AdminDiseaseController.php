<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson;
use App\Http\Requests\CreateDiseaseRequest;
use App\Http\Requests\AssignOperatorRequest;
use App\Services\AdminDiseaseService;
use Illuminate\Http\JsonResponse;
use App\Models\Disease;

class AdminDiseaseController extends Controller
{
    protected AdminDiseaseService $adminDiseaseService;

    public function __construct(AdminDiseaseService $adminDiseaseService)
    {
        $this->adminDiseaseService = $adminDiseaseService;
    }

    public function createDisease(CreateDiseaseRequest $request): JsonResponse
    {
        [$success, $message, $data] = $this->adminDiseaseService->createDisease($request->validated());

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }
}
