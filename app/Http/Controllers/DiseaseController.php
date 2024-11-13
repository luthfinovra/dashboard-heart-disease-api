<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson; // Import the ResponseJson helper
use App\Services\DiseaseService;
use App\Http\Requests\CreateDiseaseRequest;
use App\Http\Requests\EditDiseaseRequest;
use App\Http\Requests\DiseaseIdRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiseaseController extends Controller
{
    protected DiseaseService $diseaseService;

    public function __construct(DiseaseService $diseaseService)
    {
        $this->diseaseService = $diseaseService;
    }

    public function createDisease(CreateDiseaseRequest $request): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->createDisease($request->validated());

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }

    public function editDisease(EditDiseaseRequest $request, $diseaseId): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->editDisease($diseaseId, $request->validated());

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease updated successfully.', $data);
    }

    public function deleteDisease(DiseaseIdRequest $request, $diseaseId): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->deleteDisease($diseaseId);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease deleted successfully.', $data);
    }

    public function getDiseases(Request $request): JsonResponse
    {
        [$success, $message, $data]= $this->diseaseService->getDisease($request->all());

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }
        return ResponseJson::successResponse('Disease retrieved successfully.', $data);
    }

    public function getDiseaseDetails(DiseaseIdRequest $request, $diseaseId): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->getDiseaseDetails($diseaseId);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease details retrieved successfully.', $data);
    }
}
