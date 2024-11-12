<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson; // Import the ResponseJson helper
use App\Services\DiseaseRecordService;
use App\Http\Requests\CreateDiseaseRecordRequest;
use App\Http\Requests\EditDiseaseRecordRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiseaseRecordController extends Controller
{
    protected DiseaseRecordService $diseaseRecordService;

    public function __construct(DiseaseRecordService $diseaseRecordService)
    {
        $this->diseaseRecordService = $diseaseRecordService;
    }

    public function createDiseaseRecord(CreateDiseaseRecordRequest $request): JsonResponse
    {
        echo($request);
        [$success, $message, $data] = $this->diseaseRecordService->createDiseaseRecord($request->validated());

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }

    public function editDiseaseRecord(EditDiseaseRecordRequest $request, $id): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseRecordService->editDiseaseRecord($id, $request->validated());

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease record updated successfully.', $data);
    }

    public function deleteDiseaseRecord($id): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseRecordService->deleteDiseaseRecord($id);

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease record deleted successfully.', $data);
    }

    public function getDiseaseRecords($diseaseId, Request $request): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseRecordService->getDiseaseRecords($diseaseId, $request->all());

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease records retrieved successfully.', $data);
    }

    public function getDiseaseRecordDetails($id): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseRecordService->getDiseaseRecordDetails($id);

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse('Disease record details retrieved successfully.', $data);
    }
}
