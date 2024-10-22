<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Routes accessible only by admins
    Route::middleware(['checkRole:admin'])->prefix('admin')->group(function () {
        Route::post('/create-user', [AuthController::class, 'createUser']);
        Route::get('/dashboard', [AuthController::class, 'adminDashboard']);
        
    });

    // Routes accessible only by operators
    Route::middleware(['checkRole:operator'])->prefix('operator')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'operatorDashboard']);
        
    });
    
    // Routes accessible only by researchers
    Route::middleware(['checkRole:researcher'])->prefix('researcher')->group(function () {
        Route::get('/data', [AuthController::class, 'researcherData']);
        
    });
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

Route::get('/', static function () {
    return response()->json([
        'success' => true,
        'data' => [],
        'message' => 'Welcome Home'
    ]);
})->name('home');