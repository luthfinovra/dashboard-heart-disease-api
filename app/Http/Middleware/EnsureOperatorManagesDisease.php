<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseJson;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class EnsureOperatorManagesDisease
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $diseaseParam
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = auth()->id();

        $user = User::findOrFail($userId);
        // echo($user);
        // Ensure user is authenticated
        if (!$user) {
            return ResponseJson::unauthorizeResponse('Unauthorized', []);
        }

        // Allow admins to bypass the check
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check if the user is an operator and manages the specified disease

        $diseaseId = $request->route('diseaseId');
        // echo($diseaseId);

        $managesDisease = $user->managed_diseases['disease_id'];
        // echo($managesDisease);
        // echo($managesDisease === $diseaseId);
        if ($user->role === 'operator') {
            if ($managesDisease != $diseaseId) {
                return ResponseJson::forbidenResponse('Access denied: Unauthorized to manage this disease', []);
            }

            return $next($request);
        }

        return ResponseJson::forbidenResponse('Access denied', []);
    }
}
