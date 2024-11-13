<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\DiseaseRecordController;
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
    
    // Admin-only routes for user management
    Route::middleware(['checkRole:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'adminDashboard'])->name('admin.dashboard');
    
        Route::prefix('users')->group(function(){
            Route::get('/', [AdminUserController::class, 'getUsers'])->name('admin.users.index');
            Route::post('/', [AdminUserController::class, 'createUser'])->name('admin.users.create');
            Route::post('/approve/{userId}', [AdminUserController::class, 'approveUser'])->name('admin.users.approve');
            Route::post('/reject/{userId}', [AdminUserController::class, 'rejectUser'])->name('admin.users.reject');
            Route::put('/{userId}', [AdminUserController::class, 'editUser'])->name('admin.users.edit');
            Route::delete('/{userId}', [AdminUserController::class, 'deleteUser'])->name('admin.users.delete');
            Route::get('/{userId}', [AdminUserController::class, 'getUserDetails'])->name('admin.users.show');
        });
    });

    // Disease routes
    Route::prefix('diseases')->group(function(){
        Route::get('/', [DiseaseController::class, 'getDiseases'])->name('diseases.index');
        Route::get('/{diseaseId}', [DiseaseController::class, 'getDiseaseDetails'])->name('diseases.show');

        Route::middleware(['checkRole:admin'])->group(function() {
            Route::post('/', [DiseaseController::class, 'createDisease'])->name('diseases.create');
            Route::put('/{diseaseId}', [DiseaseController::class, 'editDisease'])->name('diseases.edit');
            Route::delete('/{diseaseId}', [DiseaseController::class, 'deleteDisease'])->name('diseases.delete');
        });
    });

    // Disease Records Routes
    Route::prefix('diseases/{diseaseId}/records')->group(function () {
        Route::get('/', [DiseaseRecordController::class, 'getDiseaseRecords'])->name('disease_records.index');
        Route::get('/{recordId}', [DiseaseRecordController::class, 'getDiseaseRecordDetails'])->name('disease_records.show');

        Route::middleware(['checkRole:admin,operator'])->post('/', [DiseaseRecordController::class, 'createDiseaseRecord'])->name('disease_records.store');
        Route::middleware(['checkRole:admin,operator'])->put('/{recordId}', [DiseaseRecordController::class, 'editDiseaseRecord'])->name('disease_records.update');
        Route::middleware(['checkRole:admin,operator'])->delete('/{recordId}', [DiseaseRecordController::class, 'deleteDiseaseRecord'])->name('disease_records.delete');
    });

    // Operator-only routes
    Route::middleware(['checkRole:operator'])->prefix('operator')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'operatorDashboard'])->name('operator.dashboard');
    });
    
    // Researcher-only routes
    Route::middleware(['checkRole:researcher'])->prefix('researcher')->group(function () {
        Route::get('/data', [AuthController::class, 'researcherData'])->name('researcher.data');
    });
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Root route
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'data' => [],
        'message' => 'Welcome Home'
    ]);
})->name('home');

Route::fallback(static function () {
    return response()->json([
        'success' => false,
        'data' => [],
        'message' => 'Not found'
    ]);
});