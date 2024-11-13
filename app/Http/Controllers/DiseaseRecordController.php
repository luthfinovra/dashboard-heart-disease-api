<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson; // Import the ResponseJson helper
use App\Services\DiseaseRecordService;
use App\Http\Requests\CreateDiseaseRecordRequest;
use App\Http\Requests\ShowDiseaseRecordRequest;
use App\Http\Requests\EditDiseaseRecordRequest;
use App\Http\Requests\DeleteDiseaseRecordRequest;
use App\Http\Requests\IndexDiseaseRecordRequest;
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

    public function deleteDiseaseRecord(DeleteDiseaseRecordRequest $request, $diseaseId, $recordId): JsonResponse
    {
        [$success, $message] = $this->diseaseRecordService->deleteDiseaseRecord($recordId);
    
        if (!$success) {
            return ResponseJson::failedResponse($message);
        }
    
        return ResponseJson::successResponse($message);
    }

    public function getDiseaseRecords(IndexDiseaseRecordRequest $request, $diseaseId): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseRecordService->getDiseaseRecords($diseaseId, $request->all());
    
        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }
    
        return ResponseJson::successResponse('Disease records retrieved successfully.', $data);
    }
    

    public function getDiseaseRecordDetails(ShowDiseaseRecordRequest $request, $diseaseId, $recordId): JsonResponse
    {
        [$success, $message, $data] = $this->diseaseRecordService->getDiseaseRecordDetails($diseaseId, $recordId);

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }
}