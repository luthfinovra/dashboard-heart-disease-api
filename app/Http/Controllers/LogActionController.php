<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseJson;
use Illuminate\Support\Facades\Log;
use App\Services\LogActionService;

class LogActionController extends Controller
{
    protected LogActionService $logActionService;

    public function __construct(LogActionService $logActinoService)
    {
        $this->logActionService = $logActinoService;
    }

    public function getLogActions(Request $request){

        [$success, $message, $logs] = $this->logActionService->getLogs($request->all());

        if (!$success) {
            return ResponseJson::errorResponse($message);
        }
    
        return ResponseJson::successResponse($message, $logs);
    }
}
