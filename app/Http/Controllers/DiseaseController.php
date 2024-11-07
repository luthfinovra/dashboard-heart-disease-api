<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson; // Import the ResponseJson helper
use App\Services\DiseaseService;
use App\Http\Requests\CreateDiseaseRequest;
use App\Http\Requests\EditDiseaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiseaseController extends Controller
{
    protected DiseaseService $diseaseService;

    public function __construct(DiseaseService $diseaseService)
    {
        $this->DiseaseService = $diseaseService;
    }

    public function createDisease(CreateDiseaseRequest $request): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->createDisease($request->validated()); // TO DO

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }

    public function editDisease(EditDiseaseRequest $request, $id): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->editDisease($id, $request->validated());

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease updated successfully.', $data);
    }

    public function deleteDisease($id): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->deleteDisease($id);

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

    public function getDiseaseDetails($id): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseService->getDiseaseDetails($id);

        if(!$success){
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease details retrieved successfully.', $data);
    }
}
