<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
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
        Route::get('/dashboard', [AuthController::class, 'adminDashboard'])->name('admin.dashboard');
    
        Route::post('/users', [AdminUserController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users/{id}/approve', [AdminUserController::class, 'approveUser'])->name('admin.users.approve');
        Route::post('/users/{id}/reject', [AdminUserController::class, 'rejectUser'])->name('admin.users.reject');
        Route::put('/users/{id}', [AdminUserController::class, 'editUser'])->name('admin.users.edit');
        Route::delete('/users/{id}', [AdminUserController::class, 'deleteUser'])->name('admin.users.delete');
        Route::get('/users', [AdminUserController::class, 'getUsers'])->name('admin.users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'getUserDetails'])->name('admin.users.show');
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